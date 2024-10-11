<?php

declare(strict_types=1);

namespace Telemetry\Instrumentation;

use GuzzleHttp\Psr7\ServerRequest;
use Laminas\Http\Header\HeaderInterface;
use Laminas\Http\Response;
use Laminas\Mvc\Application;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Instrumentation\CachedInstrumentation;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SemConv\TraceAttributes;
use Throwable;

use function OpenTelemetry\Instrumentation\hook;

class Laminas
{
    public static function register(): void
    {
        $instrumentation = new CachedInstrumentation(
            'io.opentelemetry.contrib.php.laminas',
            null,
            'https://opentelemetry.io/schemas/1.24.0'
        );

        /** @psalm-suppress UnusedFunctionCall */
        hook(
            Application::class,
            'run',
            pre: static function () use ($instrumentation) {
                $request = ServerRequest::fromGlobals();

                $parentContext = Globals::propagator()->extract($request->getHeaders());
                $builder = $instrumentation->tracer()->spanBuilder($request->getMethod() ?: 'app');
                $span = $builder
                    ->setParent($parentContext)
                    ->setSpanKind(SpanKind::KIND_SERVER)
                    ->setAttribute(TraceAttributes::URL_FULL, $request->getUri()->__toString())
                    ->setAttribute(TraceAttributes::HTTP_REQUEST_METHOD, $request->getMethod())
                    ->setAttribute(TraceAttributes::HTTP_REQUEST_BODY_SIZE, $request->getHeaderLine('content-length'))
                    ->setAttribute(TraceAttributes::URL_SCHEME, $request->getUri()->getScheme())
                    ->setAttribute(TraceAttributes::URL_PATH, $request->getUri()->getPath())
                    ->setAttribute(TraceAttributes::USER_AGENT_ORIGINAL, $request->getHeaderLine('user-agent'))
                    ->setAttribute(TraceAttributes::SERVER_ADDRESS, $request->getUri()->getHost())
                    ->setAttribute(TraceAttributes::SERVER_PORT, $request->getUri()->getPort())
                    ->startSpan();

                $span->activate();

                $context = $span->storeInContext($parentContext);
                Context::storage()->attach($context);
            },
            post: static function (
                Application $application,
                array $params,
                mixed $return,
                ?Throwable $exception
            ): void {
                $scope = Context::storage()->scope();
                $scope?->detach();

                if (! $scope) {
                    return;
                }

                $span = Span::fromContext($scope->context());

                if ($exception) {
                    $span->recordException($exception, [TraceAttributes::EXCEPTION_ESCAPED => true]);
                    $span->setStatus(StatusCode::STATUS_ERROR, $exception->getMessage());
                }

                $response = $application->getResponse();

                $routeName = $application->getMvcEvent()->getRouteMatch()?->getMatchedRouteName();
                $span->setAttribute(TraceAttributes::HTTP_ROUTE, $routeName);

                if ($response instanceof Response) {
                    $span->setAttribute(TraceAttributes::HTTP_RESPONSE_STATUS_CODE, $response->getStatusCode());

                    if ($response->getStatusCode() >= 500) {
                        $span->setStatus(StatusCode::STATUS_ERROR);
                    }

                    $contentLength = $response->getHeaders()->get('Content-Length');
                    if ($contentLength instanceof HeaderInterface) {
                        $span->setAttribute(
                            TraceAttributes::HTTP_RESPONSE_BODY_SIZE,
                            $contentLength->getFieldValue()
                        );
                    }
                }

                $span->end();
            }
        );
    }
}

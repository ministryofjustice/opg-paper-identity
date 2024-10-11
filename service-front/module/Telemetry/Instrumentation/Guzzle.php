<?php

declare(strict_types=1);

namespace Telemetry\Instrumentation;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Instrumentation\CachedInstrumentation;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SemConv\TraceAttributes;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Telemetry\Propagation\PsrRequest as PsrRequestPropagator;
use Throwable;

use function OpenTelemetry\Instrumentation\hook;

class Guzzle
{
    public static function register(): void
    {
        $instrumentation = new CachedInstrumentation(
            'io.opentelemetry.contrib.php.guzzle',
            null,
            'https://opentelemetry.io/schemas/1.24.0'
        );

        hook(
            ClientInterface::class,
            'transfer',
            pre: static function (ClientInterface $client, $params, string $class, string $function, ?string $filename, ?int $lineno) use ($instrumentation) {
                $request = $params[0];
                assert($request instanceof RequestInterface);

                $parentContext = Context::getCurrent();
                $propagator = Globals::propagator();

                $span = $instrumentation->tracer()
                    ->spanBuilder($request->getUri()->getHost() ?: 'unknown remote host')
                    ->setParent($parentContext)
                    ->setSpanKind(SpanKind::KIND_CLIENT)
                    ->setAttribute(TraceAttributes::URL_FULL, $request->getUri()->__toString())
                    ->setAttribute(TraceAttributes::HTTP_REQUEST_METHOD, $request->getMethod())
                    ->setAttribute(TraceAttributes::HTTP_REQUEST_BODY_SIZE, $request->getHeaderLine('Content-Length'))
                    ->setAttribute(TraceAttributes::SERVER_ADDRESS, $request->getUri()->getHost())
                    ->setAttribute(TraceAttributes::SERVER_PORT, $request->getUri()->getPort())
                    ->setAttribute(TraceAttributes::URL_PATH, $request->getUri()->getPath())
                    ->setAttribute(TraceAttributes::CODE_FUNCTION, $function)
                    ->setAttribute(TraceAttributes::CODE_NAMESPACE, $class)
                    ->setAttribute(TraceAttributes::CODE_FILEPATH, $filename)
                    ->setAttribute(TraceAttributes::CODE_LINENO, $lineno)
                    ->startSpan();

                foreach ($propagator->fields() as $field) {
                    $request = $request->withoutHeader($field);
                }

                $context = $span->storeInContext($parentContext);
                $propagator->inject($request, PsrRequestPropagator::instance(), $context);

                Context::storage()->attach($context);

                return [$request, $params[1]];
            },
            post: static function (ClientInterface $client, array $params, PromiseInterface $promise, ?Throwable $exception): void {
                $scope = Context::storage()->scope();
                $scope?->detach();

                if (!$scope || $scope->context() === Context::getCurrent()) {
                    return;
                }

                $span = Span::fromContext($scope->context());

                if ($exception) {
                    $span->recordException($exception, [TraceAttributes::EXCEPTION_ESCAPED => true]);
                    $span->setStatus(StatusCode::STATUS_ERROR, $exception->getMessage());
                    $span->end();
                }


                $promise->then(
                    onFulfilled: function (ResponseInterface $response) use ($span) {
                        $span->setAttribute(TraceAttributes::HTTP_RESPONSE_STATUS_CODE, $response->getStatusCode());
                        $span->setAttribute(TraceAttributes::HTTP_RESPONSE_BODY_SIZE, $response->getHeaderLine('Content-Length'));

                        foreach ((array) (get_cfg_var('otel.instrumentation.http.response_headers') ?: []) as $header) {
                            if ($response->hasHeader($header)) {
                                $span->setAttribute(sprintf('http.response.header.%s', strtolower($header)), $response->getHeader($header));
                            }
                        }

                        if ($response->getStatusCode() >= 500) {
                            $span->setStatus(StatusCode::STATUS_ERROR);
                        }

                        $span->end();

                        return $response;
                    },
                    onRejected: function (\Throwable $t) use ($span) {
                        $span->recordException($t, [TraceAttributes::EXCEPTION_ESCAPED => true]);
                        $span->setStatus(StatusCode::STATUS_ERROR, $t->getMessage());
                        $span->end();

                        throw $t;
                    }
                );
            }
        );
    }
}

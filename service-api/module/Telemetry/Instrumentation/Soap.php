<?php

declare(strict_types=1);

namespace Telemetry\Instrumentation;

use OpenTelemetry\API\Instrumentation\CachedInstrumentation;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SDK\Trace\Span;
use OpenTelemetry\SemConv\TraceAttributes;
use ReflectionClass;
use SoapClient;
use Throwable;

use function OpenTelemetry\Instrumentation\hook;

class Soap
{
    public static function register(): void
    {
        $instrumentation = new CachedInstrumentation(
            'io.opentelemetry.contrib.php.soap',
            null,
            'https://opentelemetry.io/schemas/1.24.0'
        );

        /** @psalm-suppress UnusedFunctionCall */
        hook(
            SoapClient::class,
            '__call',
            pre: static function (SoapClient $client, array $params) use ($instrumentation) {
                $parentContext = Context::getCurrent();

                $reflect = new ReflectionClass($client);
                $name = sprintf("%s::%s", $reflect->getShortName(), $params[0]);

                $spanBuilder = $instrumentation->tracer()
                    ->spanBuilder($name)
                    ->setParent($parentContext)
                    ->setSpanKind(SpanKind::KIND_CLIENT);

                if (isset($params[2])) {
                    $config = $params[2];
                    if (isset($config['location'])) {
                        $url = parse_url($config['location']);

                        $spanBuilder->setAttributes([
                            TraceAttributes::URL_FULL => $config['location'] ?? '',
                            TraceAttributes::SERVER_ADDRESS => $url['host'] ?? '',
                            TraceAttributes::SERVER_PORT => $url['port'] ?? '',
                            TraceAttributes::URL_PATH => $url['path'] ?? '',
                        ]);
                    }
                }

                $span = $spanBuilder->startSpan();

                $context = $span->storeInContext($parentContext);
                Context::storage()->attach($context);
            },
            post: static function (SoapClient $client, array $params, mixed $result, ?Throwable $exception): void {
                $scope = Context::storage()->scope();
                $scope?->detach();

                if (! $scope) {
                    return;
                }

                $span = Span::fromContext($scope->context());

                if ($exception) {
                    $span->recordException($exception);
                    $span->setStatus(StatusCode::STATUS_ERROR, $exception->getMessage());
                }

                $span->end();
            }
        );
    }
}

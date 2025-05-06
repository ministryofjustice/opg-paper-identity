<?php

declare(strict_types=1);

namespace Telemetry\Instrumentation;

use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\Context\Context;
use Psr\Log\LoggerInterface;
use Telemetry\Propagation\LoggingFormatter;

use function OpenTelemetry\Instrumentation\hook;

class Logger
{
    public static function register(): void
    {
        $pre = static function (LoggerInterface $object, array $params, string $class, string $function): array {
            $context = Context::getCurrent();
            $span = Span::fromContext($context)->getContext();

            if (! $span->isValid()) {
                return $params;
            }

            $ctxIdx = $function === 'log' ? 2 : 1;
            $params[$ctxIdx] ??= [];

            Globals::propagator()->inject($params[$ctxIdx], LoggingFormatter::instance());

            return $params;
        };

        foreach (['log', 'emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'] as $f) {
            hook(class: LoggerInterface::class, function: $f, pre: $pre);
        }
    }
}

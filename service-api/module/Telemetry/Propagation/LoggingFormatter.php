<?php

declare(strict_types=1);

namespace Telemetry\Propagation;

use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\Context\Propagation\PropagationSetterInterface;

class LoggingFormatter implements PropagationSetterInterface
{
    public const LOG_ENTRY_FIELD_NAME = 'trace_id';

    public static function instance(): self
    {
        static $instance;

        return $instance ??= new self();
    }

    /**
     * @param mixed $carrier
     */
    public function set(&$carrier, string $key, string $value): void
    {
        if (strtolower($key) === TraceContextPropagator::TRACEPARENT) {
            $carrier[self::LOG_ENTRY_FIELD_NAME] = explode('-', $value)[1];
        }
    }
}

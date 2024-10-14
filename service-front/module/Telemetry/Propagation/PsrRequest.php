<?php

declare(strict_types=1);

namespace Telemetry\Propagation;

use OpenTelemetry\Context\Propagation\PropagationSetterInterface;
use Psr\Http\Message\RequestInterface;

class PsrRequest implements PropagationSetterInterface
{
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
        assert($carrier instanceof RequestInterface);

        $carrier = $carrier->withHeader($key, $value);
    }
}

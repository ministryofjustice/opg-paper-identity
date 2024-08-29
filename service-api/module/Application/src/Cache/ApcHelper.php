<?php

declare(strict_types=1);

namespace Application\Cache;

class ApcHelper
{
    /**
     * @throws \Exception
     */
    public function __construct()
    {
        if (! function_exists('apcu_enabled') && apcu_enabled()) {
            throw new \Exception("APCU Cache is not available.");
        }
    }

    public function getValue(string $index): mixed
    {
        return apcu_fetch($index);
    }

    public function setValue(string $index, mixed $value): bool
    {
        return apcu_store($index, $value);
    }
}

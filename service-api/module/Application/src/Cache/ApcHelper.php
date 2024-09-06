<?php

declare(strict_types=1);

namespace Application\Cache;

use Laminas\Cache\Exception\ExceptionInterface;
use Laminas\Cache\Storage\Adapter\Apcu;

class ApcHelper
{
    private readonly Apcu $cache;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        if (! function_exists('apcu_enabled') && apcu_enabled()) {
            throw new \Exception("APCU Cache is not available.");
        }
        $this->cache = new Apcu();
    }

    /**
     * @throws ExceptionInterface
     */
    public function getValue(string $index): mixed
    {
        return $this->cache->getItem($index);
    }

    /**
     * @throws ExceptionInterface
     */
    public function setValue(string $index, mixed $value): void
    {
        $this->cache->setItem($index, $value);
    }
}

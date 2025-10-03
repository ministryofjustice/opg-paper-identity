<?php

declare(strict_types=1);

namespace Application\Aws\Secrets;

use RuntimeException;
use Stringable;

class AwsSecret implements Stringable
{
    private static ?AwsSecretsCache $cache = null;

    public function __construct(private readonly string $name)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): string
    {
        return $this->getCache()->getValue($this->getName());
    }

    public function __toString(): string
    {
        return $this->getValue();
    }

    private function getCache(): AwsSecretsCache
    {
        if (is_null(self::$cache)) {
            throw new RuntimeException('SecretsCache has not been initialised');
        }
        return self::$cache;
    }

    public static function setCache(AwsSecretsCache $cache): void
    {
        self::$cache = $cache;
    }
}

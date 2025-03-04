<?php

declare(strict_types=1);

namespace ApplicationTest\Feature\Aws\Secrets;

use Application\Aws\Secrets\AwsSecret;
use Application\Aws\Secrets\AwsSecretsCache;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class AwsSecretTest extends TestCase
{
    private AwsSecret $sut;

    public function setUp(): void
    {
        $this->sut = new AwsSecret('test');
    }

    public function testGetName(): void
    {
        self::assertEquals('test', $this->sut->getName());
    }

    public function testGetValueThrowsExceptionIfCacheNotInstantiated(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SecretsCache has not been initialised');

        $this->sut->getValue();
    }

    public function testGetValueReturnsValueFromCache(): void
    {
        $cache = $this->createMock(AwsSecretsCache::class);
        $cache->expects(self::once())
            ->method('getValue')
            ->with('test')
            ->willReturn('secretValue');

        $this->sut::setCache($cache);
        self::assertEquals('secretValue', $this->sut->getValue());
    }

    public function testToStringReturnsValueFromCache(): void
    {
        $cache = $this->createMock(AwsSecretsCache::class);
        $cache->expects(self::once())
            ->method('getValue')
            ->with('test')
            ->willReturn('secretValue');

        $this->sut::setCache($cache);
        self::assertEquals('secretValue', (string) $this->sut);
    }
}

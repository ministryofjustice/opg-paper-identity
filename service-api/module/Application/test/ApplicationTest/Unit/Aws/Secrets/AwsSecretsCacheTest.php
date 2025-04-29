<?php

declare(strict_types=1);

namespace ApplicationTest\Unit\Aws\Secrets;

use Application\Aws\Secrets\AwsSecretsCache;
use Application\Aws\Secrets\Exceptions\InvalidSecretsResponseException;
use Aws\SecretsManager\SecretsManagerClient;
use Laminas\Cache\Storage\StorageInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AwsSecretsCacheTest extends TestCase
{
    private SecretsManagerClient|MockObject $client;
    private StorageInterface|MockObject $storage;
    private AwsSecretsCache $sut;

    public function setUp(): void
    {
        $this->client = $this->createMock(SecretsManagerClient::class);
        $this->storage = $this->createMock(StorageInterface::class);
        $this->sut = new AwsSecretsCache('local/', $this->storage, $this->client);
    }

    /**
     * @psalm-suppress PossiblyUndefinedMethod
     */
    public function testGetSecretHitsExistingStorage(): void
    {
        $this->storage->expects(self::once())
            ->method('hasItem')
            ->with('aws:local/test')
            ->willReturn(true);

        $this->storage->expects(self::once())
            ->method('getItem')
            ->with('aws:local/test')
            ->willReturn('secret');

        self::assertEquals('secret', $this->sut->getValue('test'));
    }

    public static function awsResponseProvider(): array
    {
        return [
            'Return secret string' => [
                'awsResponse' => ['SecretString' => 'secret'],
            ],
            'Return secret binary' => [
                'awsResponse' => ['SecretBinary' => base64_encode('secret')],
            ],
        ];
    }

    /**
     * @psalm-suppress PossiblyUndefinedMethod
     * @psalm-suppress UndefinedMagicMethod
     */
    #[DataProvider('awsResponseProvider')]
    public function testGetUncachedSecretFromAws(array $awsResponse): void
    {
        $this->storage->expects(self::once())
            ->method('hasItem')
            ->with('aws:local/test')
            ->willReturn(false);

        $this->storage->expects(self::once())
            ->method('setItem')
            ->with('aws:local/test', 'secret');

        $this->client->expects(self::once())
            ->method('__call')
            ->with('getSecretValue', [['SecretId' => 'local/test']])
            ->willReturn($awsResponse);

        self::assertEquals('secret', $this->sut->getValue('test'));
    }

    /**
     * @psalm-suppress PossiblyUndefinedMethod
     * @psalm-suppress UndefinedMagicMethod
     */
    public function testGetSecretNonExisting(): void
    {
        $this->expectException(InvalidSecretsResponseException::class);
        $this->expectExceptionMessage('No value returned for requested key local/test');

        $this->storage->expects(self::once())
            ->method('hasItem')
            ->with('aws:local/test')
            ->willReturn(false);

        $this->client->expects(self::once())
            ->method('__call')
            ->with('getSecretValue', [['SecretId' => 'local/test']])
            ->willReturn([]);

        $this->sut->getValue('test');
    }
}

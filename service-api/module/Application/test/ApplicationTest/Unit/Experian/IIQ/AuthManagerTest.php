<?php

declare(strict_types=1);

namespace ApplicationTest\Unit\Experian\IIQ;

use Application\Experian\IIQ\AuthManager;
use Application\Experian\IIQ\WaspService;
use GuzzleHttp\Exception\ClientException;
use Laminas\Cache\Storage\StorageInterface;
use PHPUnit\Framework\TestCase;

class AuthManagerTest extends TestCase
{
    public function testAuthManagerGeneratesNewToken(): void
    {
        ClientException::class;
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects($this->once())
            ->method('hasItem')
            ->willReturn(false);
        $storage->expects($this->never())
            ->method('getItem');
        $storage->expects($this->once())
            ->method('setItem')
            ->with($this->anything(), 'mytoken');

        $waspService = $this->createMock(WaspService::class);
        $waspService->expects($this->once())
            ->method('loginWithCertificate')
            ->willReturn('mytoken');

        $authManager = new AuthManager($storage, $waspService);

        $header = $authManager->buildSecurityHeader();

        $this->assertStringContainsString('mytoken', $header->data->enc_value[0]);
    }

    public function testAuthManagerFetchesCachedToken(): void
    {
        ClientException::class;
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects($this->once())
            ->method('hasItem')
            ->willReturn(true);
        $storage->expects($this->once())
            ->method('getItem')
            ->willReturn('cachedtoken');
        $storage->expects($this->never())
            ->method('setItem');

        $waspService = $this->createMock(WaspService::class);
        $waspService->expects($this->never())
            ->method('loginWithCertificate');

        $authManager = new AuthManager($storage, $waspService);

        $header = $authManager->buildSecurityHeader();

        $this->assertStringContainsString('cachedtoken', $header->data->enc_value[0]);
    }
}

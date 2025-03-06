<?php

declare(strict_types=1);

namespace ApplicationTest\Unit\Experian\IIQ;

use Application\Experian\IIQ\Soap\WaspClient;
use Application\Experian\IIQ\WaspService;
use PHPUnit\Framework\TestCase;

class WaspServiceTest extends TestCase
{
    public function testLoginWithCertificate(): void
    {
        $client = $this->createMock(WaspClient::class);

        $client->expects($this->once())
            ->method('__call')
            ->with('LoginWithCertificate', [['service' => 'opg-paper-identity', 'checkIP' => true]])
            ->willReturn((object)['LoginWithCertificateResult' => 'mypass']);

        $sut = new WaspService($client);

        $this->assertEquals('bXlwYXNz', $sut->loginWithCertificate());
    }
}

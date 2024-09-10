<?php

declare(strict_types=1);

namespace ApplicationTest\Experian\IIQ;

use Application\Experian\IIQ\AuthManager;
use Application\Experian\IIQ\IIQService;
use Application\Experian\IIQ\Soap\IIQClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SoapFault;
use SoapHeader;

class IIQServiceTest extends TestCase
{
    private IIQClient&MockObject $iiqClient;
    private LoggerInterface&MockObject $logger;
    private AuthManager&MockObject $authManager;

    private IIQService $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->iiqClient = $this->createMock(IIQClient::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->authManager = $this->createMock(AuthManager::class);

        $this->sut = new IIQService(
            $this->authManager,
            $this->iiqClient,
            $this->logger,
        );
    }

    public function testStartAuthenticationAttempt(): void
    {
        $questions = [
            ['id' => 1],
            ['id' => 2],
        ];

        $this->authManager->expects($this->once())
            ->method('buildSecurityHeader')
            ->willReturn(new SoapHeader('placeholder', 'header'));

        $this->iiqClient->expects($this->once())
            ->method('__call')
            ->with(
                'SAA',
                $this->callback(fn ($args) => $args[0]['sAARequest']['Applicant']['Name']['Forename'] === 'Albert'),
            )
            ->willReturn((object)[
                'SAAResult' => (object)[
                    'Questions' => (object)[
                        'Question' => $questions,
                    ],
                ],
            ]);

        $this->assertEquals($questions, $this->sut->startAuthenticationAttempt());
    }

    public function testStartAuthenticationAttemptsOneRetry(): void
    {
        $soapFault = new SoapFault('0', 'Unauthorized');

        $this->authManager->expects($this->exactly(2))
            ->method('buildSecurityHeader')
            ->with($this->callback(function (bool $forceNewToken) {
                static $i = 0;
                return match (++$i) {
                    1 => $forceNewToken === false,
                    2 => $forceNewToken === true,
                    default => $this->fail("Did not expect attempt $i at calling `buildSecurityHeader`"),
                };
            }))
            ->willReturn(new SoapHeader('placeholder', 'bad-token'));

        $this->iiqClient->expects($this->exactly(2))
            ->method('__call')
            ->with('SAA', $this->anything())
            ->willThrowException($soapFault);

        $this->expectException(SoapFault::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->assertIsArray($this->sut->startAuthenticationAttempt());
    }
}

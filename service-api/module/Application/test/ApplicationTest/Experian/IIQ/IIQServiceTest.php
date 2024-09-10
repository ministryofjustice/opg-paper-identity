<?php

declare(strict_types=1);

namespace ApplicationTest\Experian\IIQ;

use Application\Experian\IIQ\AuthManager;
use Application\Experian\IIQ\ConfigBuilder;
use Application\Experian\IIQ\IIQService;
use Application\Experian\IIQ\Soap\IIQClient;
use Application\Model\Entity\CaseData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SoapHeader;
use SoapFault;

class IIQServiceTest extends TestCase
{
    private LoggerInterface|MockObject $logger;
    private ConfigBuilder|MockObject $config;
    private IIQClient&MockObject $iiqClient;
    private AuthManager&MockObject $authManager;
    private IIQService $sut;

    public function setUp(): void
    {
        parent::setUp();

        $this->iiqClient = $this->createMock(IIQClient::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->authManager = $this->createMock(AuthManager::class);
        $this->config = $this->createMock(ConfigBuilder::class);

        $this->sut = new IIQService(
            $this->authManager,
            $this->iiqClient,
            $this->config,
            $this->logger,
        );
    }

    /**
     * @return void
     * @throws \Application\Experian\IIQ\Exception\CannotGetQuestionsException
     * @psalm-suppress PossiblyUndefinedMethod
     */
    public function testStartAuthenticationAttempt(): void
    {
        $questions = [
            ['id' => 1],
            ['id' => 2],
        ];

        $caseData = CaseData::fromArray([
            'id' => '68f0bee7-5b05-41da-95c4-2f1d5952184d',
            'firstName' => 'Albert',
            'lastName' => 'Williams',
            'address' => [
                'line1' => '123 long street',
            ],
        ]);

        $saaRequest = ['Applicant' => ['Name' => ['ForeName' => 'Albert']]];
        $this->config->expects($this->once())
            ->method('buildSAA')
            ->with($caseData)
            ->willReturn($saaRequest);

        $this->authManager->expects($this->once())
            ->method('buildSecurityHeader')
            ->willReturn(new SoapHeader('placeholder', 'header'));


        $this->iiqClient->expects($this->once())
            ->method('__call')
            ->willReturn((object)[
                'SAAResult' => (object)[
                    'Questions' => (object)[
                        'Question' => $questions,
                    ],
                    'Results' => (object)[
                        'Outcome' => 'Authentication Questions returned',
                        'NextTransId' => (object)[
                            'string' => 'RTQ'
                        ]
                    ],
                ],
            ]);

        $this->assertEquals($questions, $this->sut->startAuthenticationAttempt($caseData));
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

        $caseData = CaseData::fromArray([
            'id' => '68f0bee7-5b05-41da-95c4-2f1d5952184d',
            'firstName' => 'Albert',
            'lastName' => 'Williams',
            'address' => [
                'line1' => '123 long street',
            ],
        ]);

        $this->expectException(SoapFault::class);
        $this->expectExceptionMessage('Unauthorized');

        $this->assertIsArray($this->sut->startAuthenticationAttempt($caseData));
    }
}

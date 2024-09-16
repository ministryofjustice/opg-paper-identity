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

/**
 * @psalm-import-type SAARequest from IIQService
 */
class IIQServiceTest extends TestCase
{
    private LoggerInterface|MockObject $logger;
    private IIQClient&MockObject $iiqClient;
    private AuthManager&MockObject $authManager;
    private IIQService $sut;

    /**
     * @return SAARequest
     */
    private function getSaaRequest(): array
    {
        return [
            'Applicant' => [
                'ApplicantIdentifier' => '1234',
                'Name' => ['Title' => '', 'Forename' => 'Albert', 'Surname' => 'Williams'],
                'DateOfBirth' => ['CCYY' => '1965', 'MM' => '11', 'DD' => '04'],
            ],
            'ApplicationData' => ['SearchConsent' => 'Y'],
            'LocationDetails' => [
                'LocationIdentifier' => '1',
                'UKLocation' => [
                    'HouseName' => '123 Long Street',
                    'Street' => '',
                    'District' => '',
                    'PostTown' => '',
                    'Postcode' => '',
                ],
            ],
        ];
    }

    public function setUp(): void
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

    /**
     * @return void
     * @throws \Application\Experian\IIQ\Exception\CannotGetQuestionsException
     */
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
            ->willReturn((object)[
                'SAAResult' => (object)[
                    'Control' => (object)[
                        'URN' => 'abcd',
                        'AuthRefNo' => '1234',
                    ],
                    'Questions' => (object)[
                        'Question' => $questions,
                    ],
                    'Results' => (object)[
                        'Outcome' => 'Authentication Questions returned',
                        'NextTransId' => (object)[
                            'string' => 'RTQ',
                        ],
                    ],
                ],
            ]);

        $response = $this->sut->startAuthenticationAttempt($this->getSaaRequest());

        $this->assertEquals($questions, $response['questions']);
        $this->assertEquals('abcd', $response['control']['URN']);
        $this->assertEquals('1234', $response['control']['AuthRefNo']);
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

        $this->assertIsArray($this->sut->startAuthenticationAttempt($this->getSaaRequest()));
    }
}
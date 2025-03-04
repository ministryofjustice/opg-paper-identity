<?php

declare(strict_types=1);

namespace ApplicationTest\Unit\Experian\IIQ;

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
 * @psalm-import-type RTQRequest from IIQService
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

    /**
     * @return RTQRequest
     */
    private function getRTQRequest(): array
    {
        return [
            'Control' => [
                'URN' => 'rtq-urn',
                'AuthRefNo' => 'wasp-auth-ref-no',
            ],
            'Responses' => [
                'Response' => [
                    [
                        'QuestionID' => 'QU01234',
                        'AnswerGiven' => 'SOME BANK',
                        'CustResponseFlag' => 0,
                        'AnswerActionFlag' => 'A',
                    ],
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

    public function testStartAuthenticationAttemptFailure(): void
    {
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
                    'Results' => (object)[
                        'Outcome' => 'Insufficient Questions (Unable to Authenticate)',
                        'NextTransId' => (object)[
                            'string' => 'END',
                        ],
                    ],
                ],
            ]);

        $response = $this->sut->startAuthenticationAttempt($this->getSaaRequest());

        $this->assertEquals([], $response['questions']);
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

    public function testResponseToQuestionsComplete(): void
    {
        $rtqRequest = $this->getRTQRequest();

        $this->authManager->expects($this->once())
            ->method('buildSecurityHeader')
            ->willReturn(new SoapHeader('placeholder', 'header'));

        $this->iiqClient->expects($this->once())
            ->method('__call')
            ->with('RTQ', [[
                'rTQRequest' => $rtqRequest,
            ]])
            ->willReturn((object)[
                'RTQResult' => (object)[
                    'Results' => (object)[
                        'AuthenticationResult' => 'Authenticated',
                        'NextTransId' => (object)[
                            'string' => 'END',
                        ],
                    ],
                ],
            ]);

        $response = $this->sut->responseToQuestions($rtqRequest);

        assert(isset($response['result']['AuthenticationResult']));
        $this->assertEquals('Authenticated', $response['result']['AuthenticationResult']);
        $this->assertEquals('END', $response['result']['NextTransId']->string);
    }

    public function testResponseToQuestionsMoreQuestions(): void
    {
        $questions = [
            ['id' => 1],
            ['id' => 2],
        ];

        $rtqRequest = $this->getRTQRequest();

        $this->authManager->expects($this->once())
            ->method('buildSecurityHeader')
            ->willReturn(new SoapHeader('placeholder', 'header'));

        $this->iiqClient->expects($this->once())
            ->method('__call')
            ->with('RTQ', [[
                'rTQRequest' => $rtqRequest,
            ]])
            ->willReturn((object)[
                'RTQResult' => (object)[
                    'Questions' => (object)[
                        'Question' => $questions,
                    ],
                    'Results' => (object)[
                        'NextTransId' => (object)[
                            'string' => 'RTQ',
                        ],
                    ],
                ],
            ]);

        $response = $this->sut->responseToQuestions($rtqRequest);

        assert(isset($response['questions']));
        $this->assertEquals($questions, $response['questions']);
        $this->assertEquals('RTQ', $response['result']['NextTransId']->string);
    }
}

<?php

declare(strict_types=1);

namespace ApplicationTest\Unit\Experian\IIQ;

use Application\Experian\IIQ\ConfigBuilder;
use Application\Experian\IIQ\IIQService;
use Application\Experian\IIQ\KBVService;
use Application\Fixtures\DataQueryHandler;
use Application\Fixtures\DataWriteHandler;
use Application\KBV\AnswersOutcome;
use Application\Model\Entity\CaseData;
use Application\Model\Entity\KBVQuestion;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @psalm-import-type Question from IIQService as IIQQuestion
 */
class KBVServiceTest extends TestCase
{
    public function testFetchFormattedQuestions(): void
    {
        $uuid = '68f0bee7-5b05-41da-95c4-2f1d5952184d';
        $questionsFromIIQ = [
            'questions' => [
                (object)[
                    'QuestionID' => 'QU18',
                    'Text' => 'Question Eighteen',
                    'AnswerFormat' => (object)[
                        'AnswerList' => [
                            'A',
                            'B',
                            'C',
                        ],
                    ],
                ],
                (object)[
                    'QuestionID' => 'QU93',
                    'Text' => 'Question Ninety-Three',
                    'AnswerFormat' => (object)[
                        'AnswerList' => [
                            'A',
                            'B',
                        ],
                    ],
                ],
            ],
            'control' => [
                'URN' => 'test UUID',
                'AuthRefNo' => 'abc',
            ],
        ];

        $caseData = CaseData::fromArray([
            'id' => $uuid,
            'claimedIdentity' => [
                'firstName' => 'Albert',
                'lastName' => 'Arkil',
                'dob' => '1951-02-18',
                'address' => [
                    'line1' => '123 long street',
                ]
            ]
        ]);

        $saaRequest = [
            'Applicant' => [
                'Name' => [
                    'Forename' => 'Vidal',
                    'Surname' => 'Kovacek-Orn',
                ],
            ],
        ];

        $queryHandler = $this->createMock(DataQueryHandler::class);
        $queryHandler->expects($this->once())
            ->method('getCaseByUUID')
            ->with($uuid)
            ->willReturn($caseData);

        $configBuilder = $this->createMock(ConfigBuilder::class);
        $configBuilder->expects($this->once())
            ->method('buildSAARequest')
            ->with($caseData)
            ->willReturn($saaRequest);

        $iiqService = $this->createMock(IIQService::class);
        $iiqService->expects($this->once())
            ->method('startAuthenticationAttempt')
            ->with($saaRequest)
            ->willReturn($questionsFromIIQ);

        $storedQuestions = [
            [
                'externalId' => 'QU18', 'question' => 'Question Eighteen',
                'prompts' => ['A', 'B', 'C'], 'answered' => false,
            ],
            [
                'externalId' => 'QU93', 'question' => 'Question Ninety-Three',
                'prompts' => ['A', 'B'], 'answered' => false,
            ],
        ];

        $writeHandler = $this->createMock(DataWriteHandler::class);

        $writeHandler->expects($this->once())
            ->method('updateCaseData')
            ->willReturnCallback(
            /** @psalm-suppress MissingClosureParamType */
                fn(...$params) => match (true) {
                    $params[0] === $uuid && $params[1] === 'identityIQ'
                    && isset($params[2]['kbvQuestions'])
                    && isset($params[2]['iiqControl'])
                    && $params[2]['iiqControl']['urn'] === 'test UUID'
                    && $params[2]['iiqControl']['authRefNo'] === 'abc'
                    && $params[2]['kbvQuestions'][0] instanceof KBVQuestion
                    && $params[2]['kbvQuestions'][0]->jsonSerialize() === $storedQuestions[0]
                    && $params[2]['kbvQuestions'][1] instanceof KBVQuestion
                    && $params[2]['kbvQuestions'][1]->jsonSerialize() === $storedQuestions[1] => null,
                    default => self::fail('Did not expect:' . print_r($params, true))
                }
            );

        $logger = $this->createMock(LoggerInterface::class);

        $sut = new KBVService($iiqService, $configBuilder, $queryHandler, $writeHandler, $logger);

        $this->assertEquals([
            KBVQuestion::fromArray([
                'externalId' => 'QU18',
                'question' => 'Question Eighteen',
                'prompts' => [
                    'A',
                    'B',
                    'C',
                ],
                'answered' => false,
            ]),
            KBVQuestion::fromArray([
                'externalId' => 'QU93',
                'question' => 'Question Ninety-Three',
                'prompts' => [
                    'A',
                    'B',
                ],
                'answered' => false,
            ]),
        ], $sut->fetchFormattedQuestions($uuid));
    }

    /**
     * @param IIQQuestion $newQuestion
     */
    #[DataProvider('checkAnswersProvider')]
    public function testCheckAnswers(
        string $nextTransactionId,
        ?string $authResult,
        ?object $newQuestion,
        AnswersOutcome $expectedOutcome
    ): void {
        $uuid = '5d6ee013-63fd-4e6f-81f2-961aca03b9b5';
        $iiqService = $this->createMock(IIQService::class);
        $configBuilder = $this->createMock(ConfigBuilder::class);
        $queryHandler = $this->createMock(DataQueryHandler::class);
        $writeHandler = $this->createMock(DataWriteHandler::class);
        $logger = $this->createMock(LoggerInterface::class);

        $questions = [
            [
                'externalId' => 'Q101',
                'answered' => false,
            ],
            [
                'externalId' => 'Q102',
                'answered' => false,
            ],
        ];

        $caseData = CaseData::fromArray([
            'id' => $uuid,
            'identityIQ' => [
                'kbvQuestions' => $questions,
            ]
        ]);

        $queryHandler->expects($this->once())
            ->method('getCaseByUUID')
            ->with($uuid)
            ->willReturn($caseData);

        $configBuilder->expects($this->once())
            ->method('buildRTQRequest')
            ->with([
                [
                    'experianId' => 'Q101',
                    'answer' => 'Correct Answer',
                    'flag' => '0',
                ],
                [
                    'experianId' => 'Q102',
                    'answer' => 'Big Bank Inc',
                    'flag' => '0',
                ],
            ], $caseData)
            ->willReturn(['rtqConfig']);

        $rtqResponse = [
            'result' => [
                'AuthenticationResult' => $authResult,
                'NextTransId' => (object)['string' => $nextTransactionId],
            ],
        ];

        $savedQuestions = [
            KBVQuestion::fromArray([
                'externalId' => 'Q101',
                'question' => '',
                'prompts' => [],
                'answered' => true,
            ]),
            KBVQuestion::fromArray([
                'externalId' => 'Q102',
                'question' => '',
                'prompts' => [],
                'answered' => true,
            ]),
        ];

        if ($newQuestion !== null) {
            $rtqResponse['questions'] = [$newQuestion];
            $savedQuestions[] = KBVQuestion::fromArray([
                'externalId' => $newQuestion->QuestionID,
                'question' => $newQuestion->Text,
                'prompts' => $newQuestion->AnswerFormat->AnswerList,
                'answered' => false,
            ]);
        }

        $iiqService->expects($this->once())
            ->method('responseToQuestions')
            ->with(['rtqConfig'])
            ->willReturn($rtqResponse);

        if ($nextTransactionId === 'END') {
            $writeHandler->expects($this->exactly(
                1
            ))
                ->method('updateCaseData')
                ->willReturnOnConsecutiveCalls(
                    [
                        $uuid,
                        'kbvQuestions',
                        $savedQuestions
                    ]
                );
        } else {
            $writeHandler->expects($this->once())
                ->method('updateCaseData')
                ->with(
                    $uuid,
                    'identityIQ.kbvQuestions',
                    $savedQuestions,
                );
        }

        $sut = new KBVService($iiqService, $configBuilder, $queryHandler, $writeHandler, $logger);

        $outcome = $sut->checkAnswers([
            'Q101' => 'Correct Answer',
            'Q102' => 'Big Bank Inc',
        ], $uuid);

        $this->assertEquals($expectedOutcome, $outcome);
    }

    public static function checkAnswersProvider(): array
    {
        return [
            [
                'RTQ',
                null,
                (object)[
                    'QuestionID' => 'Q103',
                    'Text' => 'What are the last two characers on your licence plate?',
                    'AnswerFormat' => (object)[
                        'AnswerList' => [
                            'SJ',
                            'FL',
                            'PE',
                        ],
                    ],
                ],
                AnswersOutcome::Incomplete,
            ],
            [
                'END',
                'Authenticated',
                null,
                AnswersOutcome::CompletePass,
            ],
            [
                'END',
                'Not Authenticated',
                null,
                AnswersOutcome::CompleteFail,
            ],
        ];
    }
}

<?php

declare(strict_types=1);

namespace ApplicationTest\Experian\IIQ;

use Application\Experian\IIQ\IIQService;
use Application\Experian\IIQ\KBVService;
use Application\Fixtures\DataQueryHandler;
use Application\Mock\KBV\KBVService as MockKBVService;
use Application\Model\Entity\CaseData;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class KBVServiceTest extends TestCase
{
    public function testFetchFormattedQuestions(): void
    {
        $uuid = '68f0bee7-5b05-41da-95c4-2f1d5952184d';
        $questions = [
            (object)[
                'QuestionID' => '1',
                'Text' => 'Question One',
                'AnswerFormat' => (object)[
                    'AnswerList' => [
                        'A',
                        'B',
                        'C'
                    ]
                ]
            ],
            (object)[
                'QuestionID' => '2',
                'Text' => 'Question Two',
                'AnswerFormat' => (object)[
                    'AnswerList' => [
                        'A',
                        'B'
                    ]
                ]
            ]
        ];

        $formattedQuestions = [
            'questionsWithoutAnswers' => [
                'one' => [
                    'number' => 'one',
                    'experianId' => '1',
                    'question' => 'Question One',
                    'prompts' => [
                        'A',
                        'B',
                        'C'
                    ]
                ],
                'two' => [
                    'number' => 'two',
                    'experianId' => '2',
                    'question' => 'Question Two',
                    'prompts' => [
                        'A',
                        'B'
                    ]
                ]
            ],
            'formattedQuestions' => [
                'one' => [
                    'number' => 'one',
                    'experianId' => '1',
                    'question' => 'Question One',
                    'prompts' => [
                        'A',
                        'B',
                        'C'
                    ]
                ],
                'two' => [
                    'number' => 'two',
                    'experianId' => '2',
                    'question' => 'Question Two',
                    'prompts' => [
                        'A',
                        'B'
                    ]
                ]
            ]
        ];

        $caseData = CaseData::fromArray([
            'id' => '68f0bee7-5b05-41da-95c4-2f1d5952184d',
            'firstName' => 'Albert',
            'lastName' => 'Arkil',
            'dob' => '1951-02-18',
            'address' => [
                'line1' => '123 long street',
            ],
        ]);

        $iiqService = $this->createMock(IIQService::class);
        $iiqService->expects($this->once())
            ->method('startAuthenticationAttempt')
            ->with($caseData)
            ->willReturn($questions);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with('Found 2 questions');


        $queryHandler = $this->createMock(DataQueryHandler::class);
        $queryHandler->expects($this->once())
            ->method('getCaseByUUID')
            ->with($uuid)
            ->willReturn($caseData);

        $sut = new KBVService($iiqService, $logger, $queryHandler);

        $this->assertEquals($formattedQuestions, $sut->fetchFormattedQuestions($uuid));
    }
}

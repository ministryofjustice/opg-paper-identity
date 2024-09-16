<?php

declare(strict_types=1);

namespace ApplicationTest\Experian\IIQ;

use Application\Experian\IIQ\ConfigBuilder;
use Application\Experian\IIQ\IIQService;
use Application\Experian\IIQ\KBVService;
use Application\Fixtures\DataQueryHandler;
use Application\Fixtures\DataWriteHandler;
use Application\Model\Entity\CaseData;
use PHPUnit\Framework\TestCase;

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
            'id' => '68f0bee7-5b05-41da-95c4-2f1d5952184d',
            'firstName' => 'Albert',
            'lastName' => 'Arkil',
            'dob' => '1951-02-18',
            'address' => [
                'line1' => '123 long street',
            ],
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

        $writeHandler = $this->createMock(DataWriteHandler::class);

        $sut = new KBVService($iiqService, $configBuilder, $queryHandler, $writeHandler);

        $this->assertEquals([
            [
                'experianId' => 'QU18',
                'question' => 'Question Eighteen',
                'prompts' => [
                    'A',
                    'B',
                    'C',
                ],
                'answered' => false,
            ],
            [
                'experianId' => 'QU93',
                'question' => 'Question Ninety-Three',
                'prompts' => [
                    'A',
                    'B',
                ],
                'answered' => false,
            ],
        ], $sut->fetchFormattedQuestions($uuid));
    }
}

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
        $questions = ['test' => 'value'];

        $caseData = CaseData::fromArray([
            'id' => '68f0bee7-5b05-41da-95c4-2f1d5952184d',
            'firstName' => 'Albert',
            'lastName' => 'Williams',
            'address' => [
                'line1' => '123 long street',
            ],
        ]);

        $iiqService = $this->createMock(IIQService::class);
        $iiqService->expects($this->once())
            ->method('startAuthenticationAttempt')
            ->with($caseData)
            ->willReturn([1, 2, 3]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with('Found 3 questions');

        $mockKbvService = $this->createMock(KBVService::class);
        $mockKbvService->expects($this->once())
            ->method('fetchFormattedQuestions')
            ->with($uuid)
            ->willReturn($questions);

        $queryHandler = $this->createMock(DataQueryHandler::class);
        $queryHandler->expects($this->once())
            ->method('getCaseByUUID')
            ->with($uuid)
            ->willReturn($caseData);

        $sut = new KBVService($iiqService, $logger, $queryHandler);

        $this->assertEquals($questions, $sut->fetchFormattedQuestions($uuid));
    }
}

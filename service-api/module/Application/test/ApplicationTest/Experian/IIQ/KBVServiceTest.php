<?php

declare(strict_types=1);

namespace ApplicationTest\Experian\IIQ;

use Application\Experian\IIQ\KBVService;
use Application\Experian\IIQ\WaspService;
use Application\Mock\KBV\KBVService as MockKBVService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class KBVServiceTest extends TestCase
{
    public function testFetchFormattedQuestions(): void
    {
        $uuid = '68f0bee7-5b05-41da-95c4-2f1d5952184d';
        $questions = ['test' => 'value'];

        $waspService = $this->createMock(WaspService::class);
        $waspService->expects($this->once())
            ->method('loginWithCertificate')
            ->willReturn('my-token');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with('Token length 8');

        $mockKbvService = $this->createMock(MockKBVService::class);
        $mockKbvService->expects($this->once())
            ->method('fetchFormattedQuestions')
            ->with($uuid)
            ->willReturn($questions);

        $sut = new KBVService($waspService, $logger, $mockKbvService);

        $this->assertEquals($questions, $sut->fetchFormattedQuestions($uuid));
    }
}

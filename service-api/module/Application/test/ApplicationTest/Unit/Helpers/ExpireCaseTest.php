<?php

declare(strict_types=1);

namespace ApplicationTest\ApplicationTest\Unit\Helpers;

use Application\Fixtures\DataWriteHandler;
use Application\Helpers\ExpireCase;
use Lcobucci\Clock\FrozenClock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Clock\ClockInterface;
use DateTimeImmutable;

class ExpireCaseTest extends TestCase
{
    private DataWriteHandler&MockObject $dataHandlerMock;
    private LoggerInterface&MockObject $loggerMock;
    private ClockInterface $clock ;

    public function setUp(): void
    {
        $this->dataHandlerMock = $this->createMock(DataWriteHandler::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->clock = new FrozenClock(new DateTimeImmutable('2025-03-10T09:00:00Z'));
    }

    public function testSetCaseExpiry(): void
    {
        $uuid = 'a9bc8ab8-389c-4367-8a9b-762ab3050999';

        $this->loggerMock->expects($this->once())
        ->method('info')
        ->with("Setting case a9bc8ab8-389c-4367-8a9b-762ab3050999 to expire in 30 days");

        $this->dataHandlerMock->expects($this->once())
            ->method('setTTL')
            ->with($uuid, '1744189200');  // epoch timestamp for 2025-04-09 09:00:00

        $expireCase = new ExpireCase($this->dataHandlerMock, $this->loggerMock, $this->clock);
        $expireCase->setCaseExpiry($uuid);
    }
}

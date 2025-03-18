<?php

declare(strict_types=1);

namespace ApplicationTest\ApplicationTest\Unit\Helpers;

use Application\Fixtures\DataWriteHandler;
use Application\Helpers\CaseOutcomeCalculator;
use Application\Model\Entity\CaseData;
use Application\Sirius\EventSender;
use Application\Sirius\UpdateStatus;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Lcobucci\Clock\FrozenClock;
use DateTimeImmutable;

class CaseOutcomeCalculatorTest extends TestCase
{
    private DataWriteHandler&MockObject $dataHandlerMock;
    private LoggerInterface&MockObject $loggerMock;
    private EventSender&MockObject $eventSenderMock;
    private CaseOutcomeCalculator $sut;

    public function setUp(): void
    {
        $this->dataHandlerMock = $this->createMock(DataWriteHandler::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->eventSenderMock = $this->createMock(EventSender::class);

        $this->sut = new CaseOutcomeCalculator(
            $this->dataHandlerMock,
            $this->loggerMock,
            $this->eventSenderMock,
            new FrozenClock(new DateTimeImmutable('2025-03-17T11:00:00Z')),
        );
    }

    /**
     * @dataProvider statusData
     */
    public function testCalculateStatus(CaseData $caseData, UpdateStatus $expectedStatus): void
    {
        $this->assertEquals(
            $this->sut->calculatestatus($caseData),
            $expectedStatus
        );
    }

    public static function statusData(): array
    {
        $uuid = '2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc';

        return [
            [
                CaseData::fromArray([
                    'id' => $uuid,
                    'idRoute' => 'OnBehalf'
                ]),
                UpdateStatus::VouchStarted
            ],
            [
                CaseData::fromArray([
                    'id' => $uuid,
                    'idRoute' => 'TELEPHONE',
                    'identityCheckPassed' => false
                ]),
                UpdateStatus::Failure
            ],
            [
                CaseData::fromArray([
                    'id' => $uuid,
                    'idRoute' => 'TELEPHONE',
                    'identityCheckPassed' => true
                ]),
                UpdateStatus::Success
            ],
            [
                CaseData::fromArray([
                    'id' => $uuid,
                    'caseProgress' => [
                        'abandonedFlow' => [
                            'last_page' => 'the/last/page',
                            'timestamp' => 'some timestamp'
                        ]
                    ]
                ]),
                UpdateStatus::Exit
            ],


        ];
    }

    /**
     * @dataProvider sendIdCheckData
     */
    public function testUpdateSendIdentityCheck(?DateTimeImmutable $inputTimestamp, string $expectedTimestamp): void
    {

        $caseData = CaseData::fromArray([
            'id' => '2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc',
            'personType' => 'donor',
            'lpas' => ['M-9387-2843-3891'],
            'idRoute' => 'TELEPHONE',
            'identityCheckPassed' => true,
        ]);

        $this->dataHandlerMock->expects($this->once())
            ->method('insertUpdateData')
            ->with($caseData);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with(
                "Sending identity check to sirius for CaseId: 2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc - Status: SUCCESS"
            );

        $this->eventSenderMock->expects($this->once())
            ->method('send')
            ->with("identity-check-updated", [
                "reference" => "opg:2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc",
                "actorType" => "donor",
                "lpaUids" => ['M-9387-2843-3891'],
                "time" => $expectedTimestamp,
                "state" => 'SUCCESS',
            ]);

        $this->dataHandlerMock->expects($this->once())
            ->method('setTTL')
            ->with('2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc');

        $this->sut->updateSendIdentityCheck($caseData, $inputTimestamp);
    }

    public static function sendIdCheckData(): array
    {
        return [
            'default timestamp' => [
                null,
                '2025-03-17T11:00:00+00:00'
            ],
            'custom timestamp' => [
                new DateTimeImmutable('2025-03-16T09:00:00Z'),
                '2025-03-16T09:00:00+00:00'
            ],
        ];
    }
}

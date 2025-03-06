<?php

declare(strict_types=1);

namespace ApplicationTest\ApplicationTest\Unit\Helpers;

use Application\Fixtures\DataWriteHandler;
use Application\Helpers\CaseOutcomeCalculator;
use Application\Model\Entity\CaseData;
use Application\Sirius\EventSender;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

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
        );
    }

    public function testUpdateSendIdentityCheck(): void
    {

        $caseData = CaseData::fromArray([
            'id' => '2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc',
            'claimedIdentity' => [
                'firstName' => 'Maria',
                'lastName' => 'Williams'
            ],
            'personType' => 'donor',
            'yotiSessionId' => 'fcb5d23c-7683-4d9b-b6de-ade49dd030fc',
            'counterService' => [
                'selectedPostOffice' => '29348729',
                'notificationsAuthToken' => '00000000-0000-0000-0000-000000000000',
                'notificationState' => '',
                'state' => '',
                'result' => false
            ],
            'lpas' => [
                'M-9387-2843-3891'
            ],
            'identityCheckPassed' => true,
        ]);

        $this->dataHandlerMock->expects($this->once())
            ->method('insertUpdateData')
            ->with($caseData);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with("Update for CaseId 2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc- Result: Passed");

        $this->eventSenderMock->expects($this->once())
            ->method('send')
            ->with("identity-check-updated", [
                "reference" => "opg:2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc",
                "actorType" => "donor",
                "lpaUids" => ['M-9387-2843-3891'],
                "time" => 'some timestamp',
                "state" => 'SUCCESS',
            ]);

        $this->sut->updateSendIdentityCheck($caseData, 'some timestamp');
    }
}

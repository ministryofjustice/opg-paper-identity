<?php

declare(strict_types=1);

namespace ApplicationTest\Services;

use Application\Fixtures\DataImportHandler;
use Application\Fixtures\DataQueryHandler;
use Application\Model\Entity\CaseData;
use Application\Yoti\SessionStatusService;
use Application\Yoti\YotiService;
use GuzzleHttp\Client;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**

 */
class SessionStatusServiceTest extends TestCase
{
    private DataQueryHandler&MockObject $dataQueryHandler;
    private DataImportHandler&MockObject $dataImportHandler;
    private YotiService&MockObject $yotiService;
    private SessionStatusService $sut;


    protected function setUp(): void
    {
        $this->client = $this->createMock(Client::class);
        $this->dataImportHandler = $this->createMock(DataImportHandler::class);
        $this->dataQueryHandler = $this->createMock(DataQueryHandler::class);
        $this->yotiService = $this->createMock(YotiService::class);

        $this->sut = new SessionStatusService(
            $this->yotiService,
            $this->dataQueryHandler,
            $this->dataImportHandler
        );
    }

    public function testNoNotificationsReturnsCounterService(): void
    {
        $caseData = CaseData::fromArray([
            'id' => '2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc',
            'firstName' => 'Maria',
            'lastName' => 'Williams',
            'personType' => 'donor',
            'yotiSessionId' => 'fcb5d23c-7683-4d9b-b6de-ade49dd030fc',
            'counterService' => [
                'selectedPostOffice' => '29348729',
                'notificationsAuthToken' => 'fcb5d23c-7683-4d9b-b6de-ade49dd030fc',
                'notificationState' => '',
                'state' => '',
                'result' => false
            ],
            'lpas' => []
        ]);

        $this->dataQueryHandler
            ->expects($this->once())->method('getCaseByUUID')
            ->with('2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc')
            ->willReturn($caseData);

        $this->yotiService->expects($this->never())->method('retrieveResults');

        $expectedResult = [
            'selectedPostOffice' => '29348729',
            'notificationsAuthToken' => 'fcb5d23c-7683-4d9b-b6de-ade49dd030fc',
            'notificationState' => '',
            'state' => '',
            'result' => false
        ];
        $result = $this->sut->getSessionStatus('2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc');

        $this->assertEquals($expectedResult, $result);

    }

    public function testFirstNotificationReturnsInProgress()
    {
        $caseData = CaseData::fromArray([
            'id' => '2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc',
            'firstName' => 'Maria',
            'lastName' => 'Williams',
            'personType' => 'donor',
            'yotiSessionId' => 'fcb5d23c-7683-4d9b-b6de-ade49dd030fc',
            'counterService' => [
                'selectedPostOffice' => '29348729',
                'notificationsAuthToken' => 'fcb5d23c-7683-4d9b-b6de-ade49dd030fc',
                'notificationState' => 'first_branch_visit',
                'state' => '',
                'result' => false
            ],
            'lpas' => []
        ]);

        $this->dataQueryHandler
            ->expects($this->once())->method('getCaseByUUID')
            ->with('2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc')
            ->willReturn($caseData);

        $this->yotiService->expects($this->never())->method('retrieveResults');

        $result = $this->sut->getSessionStatus('2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc');

        $this->assertEquals("In Progress", $result);
    }


}

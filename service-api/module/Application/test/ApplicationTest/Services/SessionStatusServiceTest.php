<?php

declare(strict_types=1);

namespace ApplicationTest\Services;

use Application\Fixtures\DataImportHandler;
use Application\Fixtures\DataQueryHandler;
use Application\Model\Entity\CaseData;
use Application\Model\Entity\CounterService;
use Application\Yoti\SessionStatusService;
use Application\Yoti\YotiService;
use InvalidArgumentException;
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

        $expectedResult = CounterService::fromArray([
            'selectedPostOffice' => '29348729',
            'notificationsAuthToken' => 'fcb5d23c-7683-4d9b-b6de-ade49dd030fc',
            'notificationState' => '',
            'state' => '',
            'result' => false
        ]);

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
            ]
        ]);

        $this->dataQueryHandler
            ->expects($this->once())->method('getCaseByUUID')
            ->with('2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc')
            ->willReturn($caseData);

        $this->yotiService->expects($this->never())->method('retrieveResults');

        $result = $this->sut->getSessionStatus('2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc');

        $this->assertEquals("In Progress", $result);
    }

    public function testResultsAreFetchedAfterSessionCompletionNotification()
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
                'notificationState' => 'session_completion',
                'state' => '',
                'result' => false
            ]
        ]);
        $response = [
            'results' => [
                'state' => 'COMPLETED',
                'checks' => [
                    [
                        'report' => [
                            'recommendation' => [
                                'value' => 'APPROVE'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->dataQueryHandler
            ->expects($this->once())->method('getCaseByUUID')
            ->with('2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc')
            ->willReturn($caseData);

        $this->yotiService
            ->expects($this->once())->method('retrieveResults')
            ->withAnyParameters()
            ->willReturn($response);

        $this->dataImportHandler
            ->expects(self::exactly(2))
            ->method('updateCaseChildAttribute')
            ->willReturnCallback(
                fn (...$parameters) => match ($parameters) {
                    [
                        '2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc',
                        'counterService.state',
                        'S',
                        'COMPLETED'
                    ],
                    [
                        '2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc',
                        'counterService.result',
                        'S',
                        true
                    ] => null,
                    default => self::fail('Did not expect:' . print_r($parameters, true))
                }
            );

        $result = $this->sut->getSessionStatus('2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc');
        $this->assertInstanceOf(CounterService::class, $result);
    }

    public function testResultsAreFetchedAfterWithOneRejectionSavesFalseResult()
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
                'notificationState' => 'session_completion',
                'state' => '',
                'result' => false
            ]
        ]);
        $response = [
            'results' => [
                'state' => 'COMPLETED',
                'checks' => [
                    [
                        'report' => [
                            'recommendation' => [
                                'value' => 'APPROVE'
                            ]
                        ]
                    ],
                    [
                        'report' => [
                            'recommendation' => [
                                'value' => 'REJECT'
                            ]
                        ]
                    ],
                    [
                        'report' => [
                            'recommendation' => [
                                'value' => 'APPROVE'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->dataQueryHandler
            ->expects($this->once())->method('getCaseByUUID')
            ->with('2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc')
            ->willReturn($caseData);

        $this->yotiService
            ->expects($this->once())->method('retrieveResults')
            ->willReturn($response);

        $this->dataImportHandler
            ->expects(self::exactly(2))
            ->method('updateCaseChildAttribute')
            ->willReturnCallback(
                fn (...$parameters) => match ($parameters) {
                    [
                        '2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc',
                        'counterService.state',
                        'S',
                        'COMPLETED'
                    ],
                    [
                        '2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc',
                        'counterService.result',
                        'S',
                        false
                    ] => null,
                    default => self::fail('Did not expect:' . print_r($parameters, true))
                }
            );

        $result = $this->sut->getSessionStatus('2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc');
        $this->assertInstanceOf(CounterService::class, $result);
    }

    public function testGetSessionStatusSessionCompletionReturnsResultsEvenIfDBSaveFails()
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
                'notificationState' => 'session_completion',
                'state' => '',
                'result' => false
            ]
        ]);
        $response = [
            'results' => [
                'state' => 'COMPLETED',
                'checks' => [
                    [
                        'report' => [
                            'recommendation' => [
                                'value' => 'APPROVE'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->dataQueryHandler
            ->method('getCaseByUUID')
            ->willReturn($caseData);

        $this->yotiService
            ->method('retrieveResults')
            ->willReturn($response);

        $this->dataImportHandler
            ->method('updateCaseChildAttribute')
            ->willThrowException(new InvalidArgumentException('Test Invalid Argument Exception'));

        $result = $this->sut->getSessionStatus('2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('state', $result);
        $this->assertArrayHasKey('result', $result);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('COMPLETED', $result['state']);
        $this->assertTrue($result['result']);
        $this->assertEquals('Test Invalid Argument Exception', $result['error']);
    }
}

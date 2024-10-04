<?php

declare(strict_types=1);

namespace ApplicationTest\Services;

use Application\Fixtures\DataWriteHandler;
use Application\Model\Entity\CaseData;
use Application\Model\Entity\CounterService;
use Application\Sirius\EventSender;
use Application\Yoti\SessionStatusService;
use Application\Yoti\YotiService;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**

 */
class SessionStatusServiceTest extends TestCase
{
    private DataWriteHandler&MockObject $dataHandler;
    private YotiService&MockObject $yotiService;
    private SessionStatusService $sut;
    private LoggerInterface&MockObject $logger;
    private EventSender&MockObject $eventSender;

    protected function setUp(): void
    {
        $this->dataHandler = $this->createMock(DataWriteHandler::class);
        $this->yotiService = $this->createMock(YotiService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->eventSender = $this->createMock(EventSender::class);

        $this->sut = new SessionStatusService(
            $this->yotiService,
            $this->dataHandler,
            $this->logger,
            $this->eventSender,
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

        $this->yotiService->expects($this->never())->method('retrieveResults');

        $expectedResult = CounterService::fromArray([
            'selectedPostOffice' => '29348729',
            'notificationsAuthToken' => 'fcb5d23c-7683-4d9b-b6de-ade49dd030fc',
            'notificationState' => '',
            'state' => '',
            'result' => false
        ]);

        $result = $this->sut->getSessionStatus($caseData);

        $this->assertEquals($expectedResult, $result);
    }

    public function testFirstNotificationReturnsInProgress(): void
    {
        $caseData = CaseData::fromArray([
            'id' => '2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc',
            'lpas' => ['M-TIU9-0TJU-84TU'],
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

        $this->yotiService->expects($this->never())->method('retrieveResults');

        $this->sut->getSessionStatus($caseData);
    }

    public function testResultsAreFetchedAfterSessionCompletionNotification(): void
    {
        $caseData = CaseData::fromArray([
            'id' => '2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc',
            'lpas' => ['M-TIU9-0TJU-84TU'],
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
                'state' => 'COMPLETED',
                'resources' => ['id_documents' => [['created_at' => '2019-04-18T14:08:18Z']]],
                'checks' => [
                    [
                        'report' => [
                            'recommendation' => [
                                'value' => 'APPROVE'
                            ]
                        ]
                    ]
                ]
        ];

        $this->yotiService
            ->expects($this->once())->method('retrieveResults')
            ->withAnyParameters()
            ->willReturn($response);

        $this->dataHandler
            ->expects(self::exactly(1))
            ->method('insertUpdateData')
            ->with($caseData);

        $this->eventSender
            ->expects($this->once())
            ->method('send')
            ->with('identity-check-resolved', [
                'reference' => 'opg:2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc',
                'actorType' => 'donor',
                'lpaIds' => ['M-TIU9-0TJU-84TU'],
                'time' => '2019-04-18T14:08:18Z',
                'outcome' => 'success',
            ]);

        $result = $this->sut->getSessionStatus($caseData);
        $this->assertInstanceOf(CounterService::class, $result);
        $this->assertTrue($result->result);
        $this->assertEquals('COMPLETED', $result->state);
    }

    public function testResultsAreFetchedAfterWithOneRejectionSavesFalseResult(): void
    {
        $caseData = CaseData::fromArray([
            'id' => '2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc',
            'lpas' => ['M-TIU9-0TJU-84TU'],
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
                'state' => 'COMPLETED',
                'resources' => ['id_documents' => [['created_at' => '2019-04-18T14:08:18Z']]],
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
        ];


        $this->yotiService
            ->expects($this->once())->method('retrieveResults')
            ->willReturn($response);

        $this->dataHandler
            ->expects(self::once())
            ->method('insertUpdateData')
            ->with($caseData);

        $this->eventSender
            ->expects($this->once())
            ->method('send')
            ->with('identity-check-resolved', [
                'reference' => 'opg:2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc',
                'actorType' => 'donor',
                'lpaIds' => ['M-TIU9-0TJU-84TU'],
                'time' => '2019-04-18T14:08:18Z',
                'outcome' => 'failure',
            ]);

        $result = $this->sut->getSessionStatus($caseData);
        $this->assertInstanceOf(CounterService::class, $result);
        $this->assertEquals('COMPLETED', $result->state);
        $this->assertFalse($result->result);
    }

    /**
     * @psalm-suppress PossiblyNullPropertyFetch
     */
    public function testGetSessionStatusSessionCompletionReturnsResultsEvenIfDBSaveFails(): void
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
        ];

        $this->yotiService
            ->method('retrieveResults')
            ->willReturn($response);

        $this->dataHandler
            ->method('insertUpdateData')
            ->willThrowException(new InvalidArgumentException('Test Invalid Argument Exception'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringStartsWith(
                    'Error updating counterService results: '
                )
            );

        $result = $this->sut->getSessionStatus($caseData);

        $this->assertEquals('COMPLETED', $result->state);
        $this->assertTrue($result->result);
    }

    /**
     * @psalm-suppress PossiblyNullPropertyFetch
     */
    public function testPassportValidityCheckIfUKPassportUsed(): void
    {
        $caseData = CaseData::fromArray([
            'id' => '2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc',
            "idMethodIncludingNation" => [
                'id_method' => "PASSPORT",
                'id_country' => "GBR",
                'id_route' => "TELEPHONE",
            ],
            'personType' => 'donor',
            'yotiSessionId' => 'fcb5d23c-7683-4d9b-b6de-ade49dd030fc',
            'counterService' => [
                'selectedPostOffice' => '29348729',
                'notificationsAuthToken' => 'fcb5d23c-7683-4d9b-b6de-ade49dd030fc',
                'notificationState' => 'session_completion',
                'state' => '',
                'result' => false
            ],
            'lpas' => []
        ]);
        $response = [
            'state' => 'COMPLETED',
            'checks' => [
                [
                    'report' => [
                        'recommendation' => [
                            'value' => 'APPROVE'
                        ]
                    ]
                ]
            ],
            'resources' => [
                'applicant_profiles' => [
                    [
                        'media' => [
                            'id' => '1e9e27b4-0586-4e86-9228-8c6db5c05252'
                        ]
                    ]
                ]
            ]
        ];

        $this->yotiService
            ->method('retrieveResults')
            ->willReturn($response);

        $this->yotiService
            ->expects($this->once())
            ->method('retrieveMedia')
            ->willReturn(["status" => "OK", "response" => ["expiration_date" => "2045-01-01"]]);

        $result = $this->sut->getSessionStatus($caseData);

        $this->assertEquals('COMPLETED', $result->state);
        $this->assertTrue($result->result);
    }
    /**
     * @psalm-suppress PossiblyNullPropertyFetch
     */
    public function testExpiredPassportReturnsAFalseResult(): void
    {
        $caseData = CaseData::fromArray([
            'id' => '2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc',
            "idMethodIncludingNation" => [
                'id_method' => "PASSPORT",
                'id_country' => "GBR",
                'id_route' => "TELEPHONE",
            ],
            'personType' => 'donor',
            'yotiSessionId' => 'fcb5d23c-7683-4d9b-b6de-ade49dd030fc',
            'counterService' => [
                'selectedPostOffice' => '29348729',
                'notificationsAuthToken' => 'fcb5d23c-7683-4d9b-b6de-ade49dd030fc',
                'notificationState' => 'session_completion',
                'state' => '',
                'result' => false
            ],
            'lpas' => []
        ]);
        $response = [
            'state' => 'COMPLETED',
            'checks' => [
                [
                    'report' => [
                        'recommendation' => [
                            'value' => 'APPROVE'
                        ]
                    ]
                ]
            ],
            'resources' => [
                'applicant_profiles' => [
                    [
                        'media' => [
                            'id' => '1e9e27b4-0586-4e86-9228-8c6db5c05252'
                        ]
                    ]
                ]
            ]
        ];
        $this->yotiService
            ->method('retrieveResults')
            ->willReturn($response);

        $this->yotiService
            ->expects($this->once())
            ->method('retrieveMedia')
            ->willReturn(["status" => "OK", "response" => ["expiration_date" => "2018-01-01"]]);

        $result = $this->sut->getSessionStatus($caseData);

        $this->assertEquals('COMPLETED', $result->state);
        $this->assertFalse($result->result);
    }

    /**
     * @psalm-suppress PossiblyNullPropertyFetch
     */
    public function testMediaRetrievalAPINotCalledIfNonUKPassport(): void
    {
        $caseData = CaseData::fromArray([
            'id' => '2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc',
            "idMethodIncludingNation" => [
                'id_method' => "PASSPORT",
                'id_country' => "",
                'id_route' => "",
            ],
            'personType' => 'donor',
            'yotiSessionId' => 'fcb5d23c-7683-4d9b-b6de-ade49dd030fc',
            'counterService' => [
                'selectedPostOffice' => '29348729',
                'notificationsAuthToken' => 'fcb5d23c-7683-4d9b-b6de-ade49dd030fc',
                'notificationState' => 'session_completion',
                'state' => '',
                'result' => false
            ],
            'lpas' => []
        ]);
        $response = [
            'state' => 'COMPLETED',
            'checks' => [
                [
                    'report' => [
                        'recommendation' => [
                            'value' => 'APPROVE'
                        ]
                    ]
                ]
            ],
            'resources' => [
                'applicant_profiles' => [
                    [
                        'media' => [
                            'id' => '1e9e27b4-0586-4e86-9228-8c6db5c05252'
                        ]
                    ]
                ]
            ]
        ];
        $this->yotiService
            ->method('retrieveResults')
            ->willReturn($response);

        $this->yotiService
            ->expects($this->never())
            ->method('retrieveMedia');

        $result = $this->sut->getSessionStatus($caseData);

        $this->assertEquals('COMPLETED', $result->state);
        $this->assertTrue($result->result);
    }
}

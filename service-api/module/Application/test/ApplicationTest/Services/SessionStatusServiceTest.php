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
use Psr\Log\LoggerInterface;

/**

 */
class SessionStatusServiceTest extends TestCase
{
    private DataImportHandler&MockObject $dataImportHandler;
    private YotiService&MockObject $yotiService;
    private SessionStatusService $sut;
    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        $this->dataImportHandler = $this->createMock(DataImportHandler::class);
        $this->yotiService = $this->createMock(YotiService::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->sut = new SessionStatusService(
            $this->yotiService,
            $this->dataImportHandler,
            $this->logger
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

    /**
     * @return void
     * @throws \Exception
     * @psalm-suppress MissingClosureParamType
     */
    public function testResultsAreFetchedAfterSessionCompletionNotification(): void
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
                        'BOOL',
                        true
                    ] => null,
                    default => self::fail('Did not expect:' . print_r($parameters, true))
                }
            );

        $result = $this->sut->getSessionStatus($caseData);
        $this->assertInstanceOf(CounterService::class, $result);
    }

    /**
     * @return void
     * @throws \Exception
     * @psalm-suppress MissingClosureParamType
     */
    public function testResultsAreFetchedAfterWithOneRejectionSavesFalseResult(): void
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
                        'BOOL',
                        false
                    ] => null,
                    default => self::fail('Did not expect:' . print_r($parameters, true))
                }
            );

        $result = $this->sut->getSessionStatus($caseData);
        $this->assertInstanceOf(CounterService::class, $result);
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

        $this->dataImportHandler
            ->method('updateCaseChildAttribute')
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
}

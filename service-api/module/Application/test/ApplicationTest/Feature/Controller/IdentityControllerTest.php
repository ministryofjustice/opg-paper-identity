<?php

declare(strict_types=1);

namespace ApplicationTest\Feature\Controller;

use Application\Controller\IdentityController;
use Application\DWP\DwpApi\DwpApiService;
use Application\Enums\DocumentType;
use Application\Enums\IdRoute;
use Application\Experian\Crosscore\FraudApi\DTO\ResponseDTO;
use Application\Experian\Crosscore\FraudApi\FraudApiService;
use Application\Fixtures\DataQueryHandler;
use Application\Fixtures\DataWriteHandler;
use Application\Helpers\CaseOutcomeCalculator;
use Application\Model\Entity\CaseData;
use Application\Model\Entity\ClaimedIdentity;
use Application\Model\Entity\IdMethod;
use Application\Sirius\EventSender;
use Application\Sirius\UpdateStatus;
use Application\Yoti\SessionConfig;
use Application\Yoti\YotiService;
use Application\Yoti\YotiServiceInterface;
use ApplicationTest\TestCase;
use DateTimeImmutable;
use Laminas\Http\Headers;
use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response;
use Laminas\Stdlib\ArrayUtils;
use Lcobucci\Clock\FrozenClock;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Clock\ClockInterface;

class IdentityControllerTest extends TestCase
{
    private DataQueryHandler&MockObject $dataQueryHandlerMock;
    private DataWriteHandler&MockObject $dataImportHandler;
    private YotiService&MockObject $yotiServiceMock;
    private SessionConfig&MockObject $sessionConfigMock;
    private FraudApiService&MockObject $experianCrosscoreFraudApiService;
    private DwpApiService&MockObject $dwpServiceMock;
    private CaseOutcomeCalculator&MockObject $caseCalcMock;

    public function setUp(): void
    {
        // The module configuration should still be applicable for tests.
        // You can override configuration here with test case specific values,
        // such as sample view templates, path stacks, module_listener_options,
        // etc.
        $configOverrides = [];

        $this->setApplicationConfig(ArrayUtils::merge(
            include __DIR__ . '/../../../../../../config/application.config.php',
            $configOverrides
        ));

        $this->dataQueryHandlerMock = $this->createMock(DataQueryHandler::class);
        $this->dataImportHandler = $this->createMock(DataWriteHandler::class);
        $this->yotiServiceMock = $this->createMock(YotiService::class);
        $this->sessionConfigMock = $this->createMock(SessionConfig::class);
        $this->experianCrosscoreFraudApiService = $this->createMock(FraudApiService::class);
        $this->dwpServiceMock = $this->createMock(DwpApiService::class);
        $this->caseCalcMock = $this->createMock(CaseOutcomeCalculator::class);

        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(DataQueryHandler::class, $this->dataQueryHandlerMock);
        $serviceManager->setService(DataWriteHandler::class, $this->dataImportHandler);
        $serviceManager->setService(YotiServiceInterface::class, $this->yotiServiceMock);
        $serviceManager->setService(SessionConfig::class, $this->sessionConfigMock);
        $serviceManager->setService(FraudApiService::class, $this->experianCrosscoreFraudApiService);
        $serviceManager->setService(DwpApiService::class, $this->dwpServiceMock);
        $serviceManager->setService(CaseOutcomeCalculator::class, $this->caseCalcMock);
    }

    public function testInvalidRouteDoesNotCrash(): void
    {
        $this->jsonHeaders();

        $this->dispatch('/invalid/route', 'GET');
        $this->assertResponseStatusCode(404);
    }

    public function jsonHeaders(): void
    {
        $headers = new Headers();
        $headers->addHeaderLine('Accept', 'application/json');

        /** @var HttpRequest $request */
        $request = $this->getRequest();
        $request->setHeaders($headers);
    }

    public function testDetailsWithUUID(): void
    {
        $this->dataQueryHandlerMock
            ->expects($this->once())->method('getCaseByUUID')
            ->with('2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc')
            ->willReturn(CaseData::fromArray([
                'id' => '2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc',
                'personType' => 'donor',
                'claimedIdentity' => [
                    'firstName' => '',
                    'lastName' => '',
                    'dob' => '',
                    'address' => [],
                ],
                'lpas' => [],
            ]));

        $this->dispatch('/identity/details?uuid=2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc', 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class);
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('details');
    }

    public function testDetailsWithNonexistentUUID(): void
    {
        $this->dispatch('/identity/details?uuid=2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc', 'GET');
        $this->assertResponseStatusCode(404);
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class);
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('details');
    }

    public function testDetailsWithNoUUID(): void
    {
        $response = '{"title":"Missing uuid"}';
        $this->dispatch('/identity/details', 'GET');
        $this->assertResponseStatusCode(400);
        $this->assertEquals($response, $this->getResponse()->getContent());
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class);
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('details');
    }

    /**
     * @param array $case
     * @param int $status
     * @return void
     * @dataProvider caseData
     */
    public function testCreate(array $case, int $status): void
    {
        $this->dispatchJSON(
            '/identity/create',
            'POST',
            $case
        );
        $this->assertResponseStatusCode($status);
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class);
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('create_case');
    }

    public static function caseData(): array
    {
        $validData = [
            'claimedIdentity' => [
                'firstName' => 'firstName',
                'lastName' => 'lastName',
                'dob' => '1980-10-10',
                'address' => [
                    'line1' => 'address 1',
                    'line2' => 'address 2',
                    'postcode' => 'GH67 7HJ'
                ]
            ],
            'lpas' => [
                'M-XYXY-YAGA-35G3',
                'M-VGAS-OAGA-34G9',
            ],
            'personType' => 'donor',
        ];

        return [
            [$validData, Response::STATUS_CODE_200],
            [array_replace_recursive($validData, ['claimedIdentity' => ['dob' => '11-11-2020']]),
                Response::STATUS_CODE_400],
            [array_replace_recursive($validData, ['lpas' => ['NAHF-AHDA-NNN']]), Response::STATUS_CODE_400],
        ];
    }


    public static function updateActionData(): array
    {
        return [
            'valid_update' => [
                'uuid' => 'a9bc8ab8-389c-4367-8a9b-762ab3050999',
                'inputData' => [
                    'claimedIdentity' => [
                        'firstName' => 'Bob',
                        'lastName' => 'Johnson',
                        'dob' => '1980-10-10'
                    ],
                ],
                'returnsMockCase' => true,
                'expectedStatus' => Response::STATUS_CODE_200
            ],
            'invalid_update_data' => [
                'uuid' => 'a9bc8ab8-389c-4367-8a9b-762ab3050999',
                'inputData' => [
                    'claimedIdentity' => [
                        'firstName' => 'Bob',
                        'lastName' => 'Smith',
                        'dob' => '10-10-1980'
                    ]
                ],
                'returnsMockCase' => true,
                'expectedStatus' => Response::STATUS_CODE_400
            ]
        ];
    }

    /**
     * @dataProvider updateActionData
     */
    public function testUpdateAction(
        string $uuid,
        array $inputData,
        bool $returnsMockCase,
        int $expectedStatus
    ): void {
        $mockCase = $this->createMock(CaseData::class);

        $mockCase->claimedIdentity = ClaimedIdentity::fromArray([
            'firstName' => $inputData['claimedIdentity']['firstName'],
            'lastName' => $inputData['claimedIdentity']['lastName'],
            'dob' => $inputData['claimedIdentity']['dob'],
            'address' => [
                'line1' => 'address 1',
                'line2' => 'address 2',
                'postcode' => 'GH67 7HJ'
            ]
        ]);

        $mockCase->lpas = [
            'M-XYXY-YAGA-35G3',
            'M-VGAS-OAGA-34G9',
        ];

        $mockCase->personType = 'donor';

        if ($returnsMockCase) {
            $mockCase->expects($this->once())
                ->method('update')
                ->with($inputData);

            if ($expectedStatus === Response::STATUS_CODE_200) {
                $this->dataImportHandler
                    ->expects($this->once())
                    ->method('insertUpdateData')
                    ->with($mockCase);
            }
        }

        $this->dataQueryHandlerMock
            ->expects($this->once())
            ->method('getCaseByUUID')
            ->with($uuid)
            ->willReturn($mockCase);

        try {
            $this->dispatchJSON('/cases/update/' . $uuid, 'PATCH', $inputData);
        } catch (\Exception $e) {
            $this->fail('Unexpected exception: ' . $e->getMessage());
        }

        $this->assertResponseStatusCode($expectedStatus);
        $this->assertMatchedRouteName('update_case');
    }

    /**
     * @dataProvider ninoData
     */
    public function testNino(
        string $nino,
        string $result,
        array $response,
        int $status
    ): void {
        $uuid = "aaaaaaaa-1111-2222-3333-000000000";
        $case = [
            "id" => $uuid,
            "personType" => "donor",
            "claimedIdentity" => [
                "firstName" => "Mary Ann",
                "lastName" => "Chapman",
                "dob" => "1949-01-01",
                "address" => [
                    "postcode" => "SW1B 1BB",
                    "country" => "UK",
                    "town" => "town",
                    "line2" => "Road",
                    "line1" => "1 Street",
                ],
                "professionalAddress" => [
                ],
            ],
            "lpas" => [
                "M-XYXY-YAGA-35G3",
                "M-VGAS-OAGA-34G9",
            ],
            "documentComplete" => false,
            "idMethod" => [
                'docType' => DocumentType::NationalInsuranceNumber->value,
                'idCountry' => "GBR",
                'idRoute' => IdRoute::KBV->value,
            ],
        ];

        $this->dataQueryHandlerMock
            ->expects($this->once())
            ->method('getCaseByUUID')
            ->with($uuid)
            ->willReturn(CaseData::fromArray($case));

        $this->dwpServiceMock->expects($this->once())
            ->method('validateNino')
            ->willReturn($result);

        $this->dispatchJSON(
            "/identity/$uuid/validate_nino",
            'POST',
            ['nino' => $nino]
        );

        $this->assertResponseStatusCode($status);
        $this->assertModuleName('application');
        $this->assertEquals(json_encode($response), $this->getResponse()->getContent());
        $this->assertControllerName(IdentityController::class);
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('validate_nino');
    }

    public static function ninoData(): array
    {
        return [
            [
                'AA112233A',
                'PASS',
                [
                    'result' => 'PASS',
                ],
                Response::STATUS_CODE_200
            ],
            [
                'AA112233E',
                'NO_MATCH',
                [
                    'result' => 'NO_MATCH',
                ],
                Response::STATUS_CODE_200
            ],
            [
                'NP123123C',
                'MULTIPLE_MATCH',
                [
                    'result' => 'MULTIPLE_MATCH',
                ],
                Response::STATUS_CODE_200
            ],
        ];
    }

    /**
     * @dataProvider drivingLicenceData
     */
    public function testDrivingLicence(string $drivingLicenceNo, string $response, int $status): void
    {
        $this->dispatchJSON(
            '/identity/validate_driving_licence',
            'POST',
            ['dln' => $drivingLicenceNo]
        );
        $this->assertResponseStatusCode($status);
        $this->assertEquals('{"status":"' . $response . '"}', $this->getResponse()->getContent());
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class); // as specified in router's controller name alias
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('validate_driving_licence');
    }

    public static function drivingLicenceData(): array
    {
        return [
            ['CHAPM301534MA9AY', 'PASS', Response::STATUS_CODE_200],
            ['SMITH710238HA3DY', 'PASS', Response::STATUS_CODE_200],
            ['SMITH720238HA3D8', 'NO_MATCH', Response::STATUS_CODE_200],
            ['JONES630536AB3J9', 'NOT_ENOUGH_DETAILS', Response::STATUS_CODE_200],
        ];
    }


    /**
     * @dataProvider passportData
     */
    public function testPassportNumber(int $passportNumber, string $response, int $status): void
    {
        $this->dispatchJSON(
            '/identity/validate_passport',
            'POST',
            ['passport' => $passportNumber]
        );
        $this->assertResponseStatusCode($status);
        $this->assertEquals('{"status":"' . $response . '"}', $this->getResponse()->getContent());
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class); // as specified in router's controller name alias
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('validate_passport');
    }

    public static function passportData(): array
    {
        return [
            [123456788, 'NO_MATCH', Response::STATUS_CODE_200],
            [123456789, 'NOT_ENOUGH_DETAILS', Response::STATUS_CODE_200],
            [123333456, 'PASS', Response::STATUS_CODE_200],
            [123456784, 'PASS', Response::STATUS_CODE_200],
        ];
    }

    /**
     * @dataProvider idMethodData
     */
    public function testUpdateIdMethodAction(CaseData $case, array $idMethod, IdMethod $expectedUpdate): void
    {
        $this->dataQueryHandlerMock
            ->expects($this->once())
            ->method('getCaseByUUID')
            ->willReturn($case);

        $this->dataImportHandler
            ->expects($this->once())
            ->method('updateCaseData')
            ->with($case->id, 'idMethod', $expectedUpdate);

        $this->dispatchJSON(
            "/cases/{$case->id}/update-id-method",
            'POST',
            $idMethod
        );
    }

    public static function idMethodData(): array
    {
        $baseCase = [
            'id' => 'a9bc8ab8-389c-4367-8a9b-762ab3050999',
            'personType' => 'donor',
            'lpas' => ['M-XYXY-YAGA-35G3',],
        ];

        $idMethod = [
            'idRoute' => IdRoute::KBV->value,
            'docType' => DocumentType::Passport->value,
            'idCountry' => 'GBR'
        ];

        $updateSingleValue = ['docType' => DocumentType::NationalInsuranceNumber->value];

        return [
            [
                CaseData::fromArray($baseCase),
                $idMethod,
                IdMethod::fromArray($idMethod),
                false
            ],
            [
                CaseData::fromArray(array_merge($baseCase, ['idMethod' => $idMethod])),
                $updateSingleValue,
                IdMethod::fromArray(array_merge($idMethod, $updateSingleValue)),
                false
            ],
        ];
    }


    public function dispatchJSON(string $path, string $method, mixed $data = null): void
    {
        $headers = new Headers();
        $headers->addHeaderLine('Accept', 'application/json');
        $headers->addHeaderLine('Content-Type', 'application/json');

        /** @var HttpRequest $request */
        $request = $this->getRequest();
        $request->setHeaders($headers);
        $request->setContent(is_string($data) ? $data : json_encode($data));

        $this->dispatch($path, $method);
    }

    /**
     * @dataProvider lpaAddData
     */
    public function testAddLpaToCase(
        string $uuid,
        string $lpa,
        CaseData $modelResponse,
        bool $stop,
        string $response
    ): void {
        $this->dataQueryHandlerMock
            ->expects($this->once())
            ->method('getCaseByUUID')
            ->willReturn($modelResponse);

        if (! $stop) {
            $this->dataImportHandler
                ->expects($this->once())
                ->method('updateCaseData');
        }

        $this->dispatchJSON(
            '/cases/' . $uuid . '/lpas/' . $lpa,
            'PUT'
        );

        $this->assertResponseStatusCode(Response::STATUS_CODE_200);
        $this->assertEquals('{"result":"' . $response . '"}', $this->getResponse()->getContent());
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class); // as specified in router's controller name alias
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('change_case_lpa/put');
    }

    public static function lpaAddData(): array
    {
        $uuid = 'a9bc8ab8-389c-4367-8a9b-762ab3050999';
        $newLpa = 'M-0000-0000-0000';
        $duplicatedLpa = 'M-XYXY-YAGA-35G3';

        $modelResponse = [
            "id" => "a9bc8ab8-389c-4367-8a9b-762ab3050999",
            "personType" => "donor",
            "claimedIdentity" => [
                "firstName" => "Mary Ann",
                "lastName" => "Chapman",
                "dob" => "1949-01-01",
                "address" => [
                    "postcode" => "SW1B 1BB",
                    "country" => "UK",
                    "town" => "town",
                    "line2" => "Road",
                    "line1" => "1 Street",
                ],
                "professionalAddress" => [
                ],
            ],
            "lpas" => [
                "M-XYXY-YAGA-35G3",
                "M-VGAS-OAGA-34G9",
            ],
            "documentComplete" => false,
            "idMethod" => [
                'docType' => "",
                'idCountry' => "",
                'idRoute' => "",
            ],
        ];

        return [
            [
                $uuid,
                $newLpa,
                CaseData::fromArray($modelResponse),
                false,
                "Updated",
            ],
            [
                $uuid,
                $duplicatedLpa,
                CaseData::fromArray($modelResponse),
                true,
                "LPA is already added to this case",
            ],
        ];
    }


    /**
     * @dataProvider lpaRemoveData
     */
    public function testRemoveLpaFromCase(
        string $uuid,
        string $lpa,
        CaseData $modelResponse,
        bool $stop,
        string $response
    ): void {
        $this->dataQueryHandlerMock
            ->expects($this->once())
            ->method('getCaseByUUID')
            ->willReturn($modelResponse);

        if (! $stop) {
            $this->dataImportHandler
                ->expects($this->once())
                ->method('updateCaseData');
        }

        $this->dispatchJSON(
            '/cases/' . $uuid . '/lpas/' . $lpa,
            'DELETE'
        );

        $this->assertResponseStatusCode(Response::STATUS_CODE_200);
        $this->assertEquals('{"result":"' . $response . '"}', $this->getResponse()->getContent());
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class); // as specified in router's controller name alias
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('change_case_lpa/delete');
    }

    public static function lpaRemoveData(): array
    {
        $uuid = 'a9bc8ab8-389c-4367-8a9b-762ab3050999';
        $notAddedLpa = 'M-0000-0000-0000';
        $addedLpa = 'M-XYXY-YAGA-35G3';

        $modelResponse = [
            "id" => "a9bc8ab8-389c-4367-8a9b-762ab3050999",
            "personType" => "donor",
            "claimedIdentity" => [
                "firstName" => "Mary Ann",
                "lastName" => "Chapman",
                "dob" => "1949-01-01",
                "address" => [
                    "postcode" => "SW1B 1BB",
                    "country" => "UK",
                    "town" => "town",
                    "line2" => "Road",
                    "line1" => "1 Street",
                ],
                "professionalAddress" => [
                ]
            ],
            "lpas" => [
                "M-XYXY-YAGA-35G3",
                "M-VGAS-OAGA-34G9",
            ],
            "documentComplete" => false,
            "idMethod" => [
                'docType' => "",
                'idCountry' => "",
                'idRoute' => "",
            ],
        ];

        return [
            [
                $uuid,
                $addedLpa,
                CaseData::fromArray($modelResponse),
                false,
                "Removed",
            ],
            [
                $uuid,
                $notAddedLpa,
                CaseData::fromArray($modelResponse),
                true,
                "LPA is not added to this case",
            ],
        ];
    }

    public function testSaveCaseProgress(): void
    {
        $uuid = 'a9bc8ab8-389c-4367-8a9b-762ab3050999';
        $data = [
            "thingOne" => "something",
            "thingTwo" => "somethingElse"
        ];
        $response = ['result' => "Progress recorded for {$uuid}"];

        $this->dataImportHandler
            ->expects($this->once())
            ->method('updateCaseData')
            ->with(
                $uuid,
                "caseProgress",
                $data,
            );

        $this->dispatchJSON("/cases/{$uuid}/save-case-progress", 'PUT', $data);
        $this->assertResponseStatusCode(Response::STATUS_CODE_200);
        $this->assertEquals($response, json_decode($this->getResponse()->getContent(), true));
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class); // as specified in router's controller name alias
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('save_case_progress/put');
    }

    /**
     * @dataProvider requestFraudCheckData
     */
    public function testRequestFraudCheck(
        string $uuid,
        CaseData $modelResponse,
        ResponseDTO $response
    ): void {

        $this->dataQueryHandlerMock
            ->expects($this->once())
            ->method('getCaseByUUID')
            ->with($uuid)
            ->willReturn($modelResponse);

        $this->experianCrosscoreFraudApiService
            ->expects($this->once())
            ->method('getFraudScore')
            ->willReturn($response);

        $path  = sprintf('/cases/%s/request-fraud-check', $uuid);

        $this->dispatchJSON(
            $path,
            'GET'
        );

        $this->assertResponseStatusCode(Response::STATUS_CODE_200);
        $this->assertEquals($response->toArray(), json_decode($this->getResponse()->getContent(), true));
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class); // as specified in router's controller name alias
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('request_fraud_check');
    }

    public static function requestFraudCheckData(): array
    {
        $uuid = 'a9bc8ab8-389c-4367-8a9b-762ab3050999';

        $modelResponse = [
            "id" => "a9bc8ab8-389c-4367-8a9b-762ab3050999",
            "personType" => "donor",
            "claimedIdentity" => [
                "firstName" => "Mary Ann",
                "lastName" => "Chapman",
                "dob" => "1949-01-01",
                "address" => [
                    "postcode" => "SW1B 1BB",
                    "country" => "UK",
                    "town" => "town",
                    "line2" => "Road",
                    "line1" => "1 Street"
                ],
                "professionalAddress" => [
                ]
            ],
            "lpas" => [
                "M-XYXY-YAGA-35G3",
                "M-VGAS-OAGA-34G9"
            ],
            "documentComplete" => false,
            "idMethod" => [
                'docType' => "",
                'idCountry' => "",
                'idRoute' => "",
            ],
        ];

        $successMockResponseData = new ResponseDTO([
            "responseHeader" => [
                "requestType" => "FraudScore",
                "clientReferenceId" => "974daa9e-8128-49cb-9728-682c72fa3801-FraudScore-continue",
                "expRequestId" => "RB000001416866",
                "messageTime" => "2024-09-03T11:19:07Z",
                "overallResponse" => [
                    "decision" => "CONTINUE",
                    "decisionText" => "Continue",
                    "decisionReasons" => [
                        "Processing completed successfully",
                        "Low Risk Machine Learning score"
                    ],
                    "recommendedNextActions" => [
                    ],
                    "spareObjects" => [
                    ]
                ],
                "responseCode" => "R0201",
                "responseType" => "INFO",
                "responseMessage" => "Workflow Complete.",
                "tenantID" => "623c97f7ff2e44528aa3fba116372d",
                "category" => "COMPLIANCE_INQUIRY"
            ],
            "clientResponsePayload" => [
                "orchestrationDecisions" => [
                    [
                        "sequenceId" => "2",
                        "decisionSource" => "MachineLearning",
                        "decision" => "ACCEPT",
                        "decisionReasons" => [
                            "Low Risk Machine Learning score"
                        ],
                        "score" => 265,
                        "decisionText" => "Continue",
                        "nextAction" => "Continue",
                        "appReference" => "",
                        "decisionTime" => "2024-09-03T11:19:08Z"
                    ]
                ],
                "decisionElements" => [
                    [
                        "serviceName" => "uk-crpverify",
                        "applicantId" => "MA_APPLICANT1",
                        "appReference" => "8H9NGXVZZV",
                        "warningsErrors" => [
                        ],
                        "otherData" => [
                            "response" => [
                                "contactId" => "MA1",
                                "nameId" => "MANAME1",
                                "uuid" => "75467c7e-c7ea-4f3a-b02e-3fd0793191b5"
                            ]
                        ],
                        "auditLogs" => [
                            [
                                "eventType" => "BUREAU DATA",
                                "eventDate" => "2024-09-03T11:19:08Z",
                                "eventOutcome" => "No Match Found"
                            ]
                        ]
                    ],
                    [
                        "serviceName" => "MachineLearning",
                        "normalizedScore" => 100,
                        "score" => 265,
                        "appReference" => "fraud-score-1.0",
                        "otherData" => [
                            "probabilities" => [
                                0.73476599388745,
                                0.26523400611255
                            ],
                            "probabilityMultiplier" => 1000,
                            "modelInputs" => [
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                -1,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                -1,
                                -1,
                                0,
                                0,
                                -1
                            ]
                        ],
                        "decisions" => [
                            [
                                "element" => "Reason 1",
                                "value" => "6.7",
                                "reason" => "PA04 - Number of previous vehicle financing applications"
                            ]
                        ]
                    ]
                ]
            ],
            "originalRequestData" => [
                "contacts" => [
                    [
                        "id" => "MA1",
                        "person" => [
                            "personDetails" => [
                                "dateOfBirth" => "1986-09-03"
                            ],
                            "personIdentifier" => "",
                            "names" => [
                                [
                                    "type" => "CURRENT",
                                    "firstName" => "lee",
                                    "surName" => "manthrope",
                                    "middleNames" => "",
                                    "id" => "MANAME1"
                                ]
                            ]
                        ],
                        "addresses" => [
                            [
                                "id" => "MACADDRESS1",
                                "addressType" => "CURRENT",
                                "indicator" => "RESIDENTIAL",
                                "buildingNumber" => "18",
                                "postal" => "SO15 3AA",
                                "street" => "BOURNE COURT",
                                "postTown" => "southampton",
                                "county" => ""
                            ]
                        ]
                    ]
                ],
                "control" => [
                    [
                        "option" => "ML_MODEL_CODE",
                        "value" => "bfs"
                    ]
                ],
                "application" => [
                    "applicants" => [
                        [
                            "id" => "MA_APPLICANT1",
                            "contactId" => "MA1",
                            "type" => "INDIVIDUAL",
                            "applicantType" => "MAIN_APPLICANT",
                            "consent" => "true"
                        ]
                    ]
                ],
                "source" => ""
            ]
        ]);

        return [
            [
                $uuid,
                CaseData::fromArray($modelResponse),
                $successMockResponseData,
            ],
        ];
    }

    public function testSendIdentityCheckAction(): void
    {
        $eventSenderMock = $this->createMock(EventSender::class);
        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(EventSender::class, $eventSenderMock);

        $uuid = 'a9bc8ab8-389c-4367-8a9b-762ab3050999';

        $caseData = CaseData::fromArray([
            'id' => $uuid,
            'personType' => 'donor',
            'lpas' => [
                'M-XYXY-YAGA-35G3',
                'M-VGAS-OAGA-34G9',
            ],
        ]);

        $this->dataQueryHandlerMock
            ->expects($this->once())
            ->method('getCaseByUUID')
            ->with($uuid)
            ->willReturn($caseData);

        $this->caseCalcMock
            ->expects($this->once())
            ->method('updateSendIdentityCheck')
            ->with($caseData);

        $this->dispatchJSON("/cases/{$uuid}/send-identity-check", 'POST');
        $this->assertResponseStatusCode(Response::STATUS_CODE_200);
    }

    public function testSendSiriusEventActionWithMissingCase(): void
    {
        $uuid = 'a9bc8ab8-389c-4367-8a9b-762ab3050999';

        $this->dataQueryHandlerMock
            ->expects($this->once())
            ->method('getCaseByUUID')
            ->with($uuid)
            ->willReturn(null);

        $this->dispatchJSON("/cases/{$uuid}/send-identity-check", 'POST');

        $this->assertResponseStatusCode(Response::STATUS_CODE_404);
        $body = json_decode($this->getResponse()->getContent(), true);

        $this->assertEquals('Case not found', $body['title']);
    }
}

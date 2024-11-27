<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Controller\IdentityController;
use Application\Experian\Crosscore\FraudApi\DTO\ResponseDTO;
use Application\Experian\Crosscore\FraudApi\FraudApiService;
use Application\Fixtures\DataQueryHandler;
use Application\Fixtures\DataWriteHandler;
use Application\Model\Entity\CaseData;
use Application\Yoti\SessionConfig;
use Application\Yoti\YotiService;
use Application\Yoti\YotiServiceInterface;
use ApplicationTest\TestCase;
use Laminas\Http\Headers;
use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response;
use Laminas\Stdlib\ArrayUtils;
use PHPUnit\Framework\MockObject\MockObject;

class IdentityControllerTest extends TestCase
{
    private DataQueryHandler&MockObject $dataQueryHandlerMock;
    private DataWriteHandler&MockObject $dataImportHandler;
    private YotiService&MockObject $yotiServiceMock;
    private SessionConfig&MockObject $sessionConfigMock;
    private FraudApiService&MockObject $experianCrosscoreFraudApiService;

    public function setUp(): void
    {
        // The module configuration should still be applicable for tests.
        // You can override configuration here with test case specific values,
        // such as sample view templates, path stacks, module_listener_options,
        // etc.
        $configOverrides = [];

        $this->setApplicationConfig(ArrayUtils::merge(
            include __DIR__ . '/../../../../config/application.config.php',
            $configOverrides
        ));

        $this->dataQueryHandlerMock = $this->createMock(DataQueryHandler::class);
        $this->dataImportHandler = $this->createMock(DataWriteHandler::class);
        $this->yotiServiceMock = $this->createMock(YotiService::class);
        $this->sessionConfigMock = $this->createMock(SessionConfig::class);
        $this->experianCrosscoreFraudApiService = $this->createMock(FraudApiService::class);


        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(DataQueryHandler::class, $this->dataQueryHandlerMock);
        $serviceManager->setService(DataWriteHandler::class, $this->dataImportHandler);
        $serviceManager->setService(YotiServiceInterface::class, $this->yotiServiceMock);
        $serviceManager->setService(SessionConfig::class, $this->sessionConfigMock);
        $serviceManager->setService(FraudApiService::class, $this->experianCrosscoreFraudApiService);
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
            [array_replace_recursive($validData, ['claimedIdentity' => ['lastName' => '']]), Response::STATUS_CODE_400],
            [array_replace_recursive($validData, ['claimedIdentity' => ['dob' => '11-11-2020']]),
                Response::STATUS_CODE_400],
            [array_replace_recursive($validData, ['lpas' => ['NAHF-AHDA-NNN']]), Response::STATUS_CODE_400],
        ];
    }

    /**
     * @dataProvider ninoData
     */
    public function testNino(string $nino, string $response, int $status): void
    {
        $this->dispatchJSON(
            '/identity/validate_nino',
            'POST',
            ['nino' => $nino]
        );
        $this->assertResponseStatusCode($status);
        $this->assertModuleName('application');
        $this->assertEquals('{"status":"' . $response . '"}', $this->getResponse()->getContent());
        $this->assertControllerName(IdentityController::class); // as specified in router's controller name alias
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('validate_nino');
    }

    public static function ninoData(): array
    {
        return [
            ['AA112233A', 'PASS', Response::STATUS_CODE_200],
            ['BB112233A', 'PASS', Response::STATUS_CODE_200],
            ['AA112233D', 'NOT_ENOUGH_DETAILS', Response::STATUS_CODE_200],
            ['AA112233C', 'NO_MATCH', Response::STATUS_CODE_200],
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
                ]
            ],
            "lpas" => [
                "M-XYXY-YAGA-35G3",
                "M-VGAS-OAGA-34G9",
            ],
            "documentComplete" => false,
            "alternateAddress" => [
            ],
            "searchPostcode" => null,
            "idMethodIncludingNation" => [
                'id_method' => "",
                'id_country' => "",
                'id_route' => "",
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
                ]
            ],
            "lpas" => [
                "M-XYXY-YAGA-35G3",
                "M-VGAS-OAGA-34G9",
            ],
            "documentComplete" => false,
            "alternateAddress" => [
            ],
            "searchPostcode" => null,
            "idMethodIncludingNation" => [
                'id_method' => "",
                'id_country' => "",
                'id_route' => "",
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

    /**
     * @dataProvider saveCaseProgressData
     */
    public function testSaveCaseProgress(
        string $uuid,
        array $data,
        array $response
    ): void {

        $this->dataImportHandler
            ->expects($this->once())
            ->method('updateCaseData')
            ->with(
                $uuid,
                "caseProgress",
                $data,
            );

        $path = sprintf('/cases/%s/save-case-progress', $uuid);

        $this->dispatchJSON(
            $path,
            'PUT',
            $data
        );

        $this->assertResponseStatusCode(Response::STATUS_CODE_200);
        $this->assertEquals($response, json_decode($this->getResponse()->getContent(), true));
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class); // as specified in router's controller name alias
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('save_case_progress/put');
    }

    public static function saveCaseProgressData(): array
    {
        $uuid = 'a9bc8ab8-389c-4367-8a9b-762ab3050999';
        $data = [
            "last_page" => "name-match-check",
            "timestamp" => "2024-09-12T13:45:56Z"
        ];
        $response = json_decode('{"result":"Progress recorded at ' . $uuid . '/' . $data['last_page'] . '"}', true);

        return [
            [
                $uuid,
                $data,
                $response,
            ],
        ];
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
                ]
            ],
            "lpas" => [
                "M-XYXY-YAGA-35G3",
                "M-VGAS-OAGA-34G9"
            ],
            "documentComplete" => false,
            "alternateAddress" => [
            ],
            "searchPostcode" => null,
            "idMethodIncludingNation" => [
                'id_method' => "",
                'id_country' => "",
                'id_route' => "",
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
}

<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Controller\IdentityController;
use Application\Fixtures\DataImportHandler;
use Application\Fixtures\DataQueryHandler;
use Application\KBV\KBVServiceInterface;
use Application\Model\Entity\CaseData;
use Application\Yoti\SessionConfig;
use Application\Yoti\YotiService;
use Application\Yoti\YotiServiceInterface;
use ApplicationTest\TestCase;
use Laminas\Http\Headers;
use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response;
use Laminas\Stdlib\ArrayUtils;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;

class IdentityControllerTest extends TestCase
{
    private DataQueryHandler&MockObject $dataQueryHandlerMock;
    private KBVServiceInterface&MockObject $KBVServiceMock;
    private DataImportHandler&MockObject $dataImportHandler;
    private YotiService&MockObject $yotiServiceMock;
    private SessionConfig&MockObject $sessionConfigMock;

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
        $this->KBVServiceMock = $this->createMock(KBVServiceInterface::class);
        $this->dataImportHandler = $this->createMock(DataImportHandler::class);
        $this->yotiServiceMock = $this->createMock(YotiService::class);
        $this->sessionConfigMock = $this->createMock(SessionConfig::class);


        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(DataQueryHandler::class, $this->dataQueryHandlerMock);
        $serviceManager->setService(DataImportHandler::class, $this->dataImportHandler);
        $serviceManager->setService(KBVServiceInterface::class, $this->KBVServiceMock);
        $serviceManager->setService(YotiServiceInterface::class, $this->yotiServiceMock);
        $serviceManager->setService(SessionConfig::class, $this->sessionConfigMock);
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
                'firstName' => '',
                'lastName' => '',
                'dob' => '',
                'lpas' => [],
                'address' => [],
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
            'firstName' => 'firstName',
            'lastName' => 'lastName',
            'personType' => 'donor',
            'dob' => '1980-10-10',
            'lpas' => [
                'M-XYXY-YAGA-35G3',
                'M-VGAS-OAGA-34G9'
            ],
            'address' => [
                'address 1, address 2'
            ]
        ];

        return [
            [$validData, Response::STATUS_CODE_200],
            [array_merge($validData, ['lastName' => '']), Response::STATUS_CODE_400],
            [array_merge($validData, ['dob' => '11-11-2020']), Response::STATUS_CODE_400],
            [array_replace_recursive($validData, ['lpas' => ['NAHF-AHDA-NNN']]), Response::STATUS_CODE_400],
        ];
    }

    /**
     * @dataProvider kbvAnswersData
     */
    public function testKbvAnswers(
        string $uuid,
        array $provided,
        CaseData $actual,
        string $result,
        int $status
    ): void {
        if ($result !== 'error') {
            $this->dataQueryHandlerMock
                ->expects($this->once())->method('getCaseByUUID')
                ->with($uuid)
                ->willReturn($actual);
        }

        $this->dispatchJSON(
            '/cases/' . $uuid . '/kbv-answers',
            'POST',
            $provided
        );
        $this->assertResponseStatusCode($status);
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class);
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('check_kbv_answers');

        if ($result === "error") {
            $response = json_decode($this->getResponse()->getContent(), true);
            $this->assertEquals('Missing UUID or unable to find case', $response['title']);
        } else {
            $this->assertEquals('{"result":"' . $result . '"}', $this->getResponse()->getContent());
        }
    }

    public static function kbvAnswersData(): array
    {
        $uuid = 'e32a4d31-f15b-43f8-9e21-2fb09c8f45e7';
        $invalidUUID = 'asdkfh3984ahksdjka';
        $provided = [
            'answers' => [
                'one' => 'VoltWave',
                'two' => 'Germanotta',
                'tree' => 'July',
                'four' => 'Pink'
            ]
        ];
        $providedIncomplete = $provided;
        unset($providedIncomplete['answers']['four']);

        $providedIncorrect = $provided;
        $providedIncorrect['answers']['two'] = 'incorrect answer';

        $actual = CaseData::fromArray([
            'personType' => 'donor',
            'firstName' => '',
            'lastName' => '',
            'dob' => '',
            'lpas' => [],
            'address' => [],
        ]);

        $actual->kbvQuestions = json_encode([
            'one' => ['answer' => 'VoltWave'],
            'two' => ['answer' => 'Germanotta'],
            'tree' => ['answer' => 'July'],
            'four' => ['answer' => 'Pink']
        ]);

        return [
            [$uuid, $provided, $actual, 'pass', Response::STATUS_CODE_200],
            [$uuid, $providedIncomplete, $actual, 'fail', Response::STATUS_CODE_200],
            [$uuid, $providedIncorrect, $actual, 'fail', Response::STATUS_CODE_200],
            [$invalidUUID, $provided, $actual, 'error', Response::STATUS_CODE_400],
        ];
    }

    public function testKBVQuestionsWithNoUUID(): void
    {
        $this->dispatch('/cases/kbv-questions', 'GET');
        $this->assertResponseStatusCode(400);
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class);
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('get_kbv_questions');

        $response = json_decode($this->getResponse()->getContent(), true);
        $this->assertEquals('Missing UUID', $response['title']);
    }

    /**
     * @throws Exception
     */
    public function testKBVQuestionsWithVerifiedDocsCaseGeneratesQuestions(): void
    {
        $caseData = CaseData::fromArray([
            'personType' => '',
            'firstName' => 'test',
            'lastName' => 'name',
            'dob' => '',
            'lpas' => [],
            'address' => [],
        ]);

        $caseData->documentComplete = true;

        $formattedQuestions = $this->formattedQuestions();

        $this->dataQueryHandlerMock
            ->expects($this->once())->method('getCaseByUUID')
            ->with('a9bc8ab8-389c-4367-8a9b-762ab3050999')
            ->willReturn($caseData);

        $this->KBVServiceMock
            ->expects($this->once())->method('fetchFormattedQuestions')
            ->with('a9bc8ab8-389c-4367-8a9b-762ab3050999')
            ->willReturn($formattedQuestions);

        $this->dispatch('/cases/a9bc8ab8-389c-4367-8a9b-762ab3050999/kbv-questions', 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertStringContainsString('Who is your electricity supplier?', $this->getResponse()->getContent());
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class);
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('get_kbv_questions');
    }

    /**
     * @throws Exception
     */
    public function testKBVQuestionsWithVerifiedDocsCaseAndExistingQuestions(): void
    {
        $caseData = CaseData::fromArray([
            'personType' => '',
            'firstName' => 'test',
            'lastName' => 'name',
            'dob' => '',
            'lpas' => [],
            'address' => [],
        ]);

        $caseData->kbvQuestions = json_encode($this->formattedQuestions());
        $caseData->documentComplete = true;

        $this->dataQueryHandlerMock
            ->expects($this->once())->method('getCaseByUUID')
            ->with('a9bc8ab8-389c-4367-8a9b-762ab3050999')
            ->willReturn($caseData);

        $this->KBVServiceMock
            ->expects($this->never())->method('fetchFormattedQuestions')
            ->with('a9bc8ab8-389c-4367-8a9b-762ab3050999');

        $this->dispatch('/cases/a9bc8ab8-389c-4367-8a9b-762ab3050999/kbv-questions', 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertStringContainsString('Who is your electricity supplier?', $this->getResponse()->getContent());
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class);
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('get_kbv_questions');
    }

    /**
     * @throws Exception
     */
    public function testKBVQuestionsWithUnVerifiedDocsCase(): void
    {
        $response = '{"error":"Document checks incomplete or unable to locate case"}';
        $caseData = CaseData::fromArray([
            'personType' => '',
            'firstName' => 'test',
            'lastName' => 'name',
            'dob' => '',
            'lpas' => [],
            'address' => [],
        ]);

        $this->dataQueryHandlerMock->expects($this->once())->method('getCaseByUUID')
            ->with('a9bc8ab8-389c-4367-8a9b-762ab3050999')
            ->willReturn($caseData);

        $this->dispatch('/cases/a9bc8ab8-389c-4367-8a9b-762ab3050999/kbv-questions', 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertEquals($response, $this->getResponse()->getContent());
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class);
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('get_kbv_questions');
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
            ['AA112233C', 'NO_MATCH', Response::STATUS_CODE_200]
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
            ['JONES630536AB3J9', 'NOT_ENOUGH_DETAILS', Response::STATUS_CODE_200]
        ];
    }

    public function testDocumentCompleteUpdateStartsYotiProcess(): void
    {
        $uuid = 'test-uuid';
        $caseData = CaseData::fromArray([
            'id' => 'test-uuid',
            'firstName' => 'test',
            'lastName' => 'opg',
            'dob' => '1980-01-01',
            'address' => ['123 upper road'],
            'personType' => 'donor',
            'idMethod' => 'po_ukp'
        ]);

        $sessionData = $this->sessionConfig($caseData);
        $response = [];
        $response["status"] = 201;
        $response["data"] = [
            "client_session_token_ttl" => 2630012,
            "session_id" => "19eb9325-61ed-4089-88dc-5bbc659443d3",
            "client_session_token" => "1c9f8e92-3a04-463e-9dd1-98dad2b657f2"
        ];
        $pdfResponse = ["status" => "PDF Created"];
        $pdfLetter = ["status" => "PDF Created", "pdfData" => "contents"];

        $this->dataQueryHandlerMock
            ->expects($this->atLeastOnce())->method('getCaseByUUID')
            ->with($uuid)
            ->willReturn($caseData);

        $this->sessionConfigMock
            ->expects($this->once())->method('build')
            ->with($caseData)
            ->willReturn($sessionData);

        $this->yotiServiceMock
            ->expects($this->once())->method('createSession')
            ->with($sessionData)
            ->willReturn($response);

        $this->yotiServiceMock
            ->expects($this->once())->method('preparePDFLetter')
            ->with($caseData)
            ->willReturn($pdfResponse);

        $this->yotiServiceMock
            ->expects($this->once())->method('retrieveLetterPDF')
            ->with($response["data"]["session_id"])
            ->willReturn($pdfLetter);

        $this->dataImportHandler
            ->expects($this->atLeast(2))->method('updateCaseData');

        $this->dispatch('/cases/test-uuid/complete-document', 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class);
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('complete_document');
    }

    public function testYotiProcessIsNotTriggeredForNonePostOfficeMethods(): void
    {
        $uuid = 'test-uuid';
        $caseData = CaseData::fromArray([
            'id' => 'test-uuid',
            'firstName' => 'test',
            'lastName' => 'opg',
            'dob' => '1980-01-01',
            'address' => ['123 upper road'],
            'personType' => 'donor',
            'idMethod' => 'dln'
        ]);
        $this->dataQueryHandlerMock
            ->expects($this->atLeastOnce())->method('getCaseByUUID')
            ->with($uuid)
            ->willReturn($caseData);

        $this->sessionConfigMock
            ->expects($this->never())->method('build');

        $this->yotiServiceMock
            ->expects($this->never())->method('createSession');

        $this->dataImportHandler
            ->expects($this->atLeast(1))->method('updateCaseData');

        $this->dispatch('/cases/test-uuid/complete-document', 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class);
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('complete_document');
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

    public function formattedQuestions(): array
    {
        return [
            'formattedQuestions' => [
                'one' => [
                    'question' => 'Who is your electricity supplier?',
                    'prompts' => [
                        0 => 'VoltWave',
                        1 => 'Glow Electric',
                        2 => 'Powergrid Utilities',
                        3 => 'Bright Bristol Power'
                    ],
                    'answer' => 'VoltWave'
                ],
                'two' => [
                    'question' => 'How much was your last phone bill?',
                    'prompts' => [
                        0 => "£5.99",
                        1 => "£11",
                        2 => "£16.84",
                        3 => "£1.25"
                    ],
                    'answer' => "£5.99"
                ]
            ],
            'questionsWithoutAnswers' => [
                'one' => [
                    'question' => 'Who is your electricity supplier?',
                    'prompts' => [
                        0 => 'VoltWave',
                        1 => 'Glow Electric',
                        2 => 'Powergrid Utilities',
                        3 => 'Bright Bristol Power'
                    ]
                ],
                'two' => [
                    'question' => 'How much was your last phone bill?',
                    'prompts' => [
                        0 => "£5.99",
                        1 => "£11",
                        2 => "£16.84",
                        3 => "£1.25"
                    ]
                ]
            ],
        ];
    }

    public function sessionConfig(CaseData $case): array
    {
        $sessionConfig = [];

        $sessionConfig["session_deadline"] = '2025-05-05 22:00:00';
        $sessionConfig["user_tracking_id"] = $case->id;

        $sessionConfig["requested_checks"] = [
            [
                "type" => "PROFILE_DOCUMENT_MATCH",
                "config" => [
                    "manual_check" => "IBV"
                ]
            ],
        ];
        $sessionConfig["required_documents"] = [
            [
                "type" => "ID_DOCUMENT",
                "filter" => [
                    "type" => "DOCUMENT_RESTRICTIONS",
                    "inclusion" => "INCLUDE",
                    "documents" => [
                        [
                            "country_codes" => ["GBR"],
                            "document_types" => ["PASSPORT"]
                        ]
                    ]
                ]
            ]
        ];
        $sessionConfig["resources"] = [
            "applicant_profile" => [
                "given_names" => $case->firstName,
                "family_name" => $case->lastName,
                "date_of_birth" => $case->dob,
                "structured_postal_address" => [],
            ]
        ];

        return $sessionConfig;
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
            "lpas" => [
                "M-XYXY-YAGA-35G3",
                "M-VGAS-OAGA-34G9"
            ],
            "documentComplete" => false,
            "alternateAddress" => [
            ],
            "searchPostcode" => null,
            "idMethod" => null,
            "idMethodIncludingNation" => [
            ]
        ];

        return [
            [
                $uuid,
                $newLpa,
                CaseData::fromArray($modelResponse),
                false,
                "Updated"
            ],
            [
                $uuid,
                $duplicatedLpa,
                CaseData::fromArray($modelResponse),
                true,
                "LPA is already added to this case"
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
            "lpas" => [
                "M-XYXY-YAGA-35G3",
                "M-VGAS-OAGA-34G9"
            ],
            "documentComplete" => false,
            "alternateAddress" => [
            ],
            "searchPostcode" => null,
            "idMethod" => null,
            "idMethodIncludingNation" => [
            ]
        ];

        return [
            [
                $uuid,
                $addedLpa,
                CaseData::fromArray($modelResponse),
                false,
                "Removed"
            ],
            [
                $uuid,
                $notAddedLpa,
                CaseData::fromArray($modelResponse),
                true,
                "LPA is not added to this case"
            ],
        ];
    }

    /**
     * @dataProvider abandonFlowData
     */
    public function testAbandonFlow(
        string $uuid,
        array $data,
        array $response
    ): void {

        $this->dataImportHandler
            ->expects($this->once())
            ->method('updateCaseData')
            ->with(
                $uuid,
                "progressPage",
                "M",
                array_map(fn (mixed $v) => [
                    'S' => $v
                ], $data),
            );

        $path  = sprintf('/cases/%s/update-progress', $uuid);

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
        $this->assertMatchedRouteName('update_progress/put');
    }

    public static function abandonFlowData(): array
    {
        $uuid = 'a9bc8ab8-389c-4367-8a9b-762ab3050999';
        $data = [
            "route" => "name-match-check",
            "reason" => "ot",
            "notes" => "Caller didn't have all required documents"
        ];
        $response = json_decode('{"result":"Progress recorded at ' . $uuid . '/' . $data['route'] . '"}', true);

        return [
            [
                $uuid,
                $data,
                $response
            ],
        ];
    }
}

<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Controller\YotiController;
use Application\Fixtures\DataImportHandler;
use Application\Fixtures\DataQueryHandler;
use Application\Model\Entity\CaseData;
use Application\Yoti\Http\Exception\YotiException;
use Application\Yoti\SessionConfig;
use Application\Yoti\YotiService;
use Application\Yoti\YotiServiceInterface;
use ApplicationTest\TestCase;
use Laminas\Http\Headers;
use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response;
use Laminas\Stdlib\ArrayUtils;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid\Uuid;

class YotiControllerTest extends TestCase
{
    private YotiService&MockObject $YotiServiceMock;

    private DataQueryHandler&MockObject $dataQueryHandlerMock;

    private DataImportHandler&MockObject $dataImportHandler;

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

        $this->YotiServiceMock = $this->createMock(YotiService::class);
        $this->dataQueryHandlerMock = $this->createMock(DataQueryHandler::class);
        $this->dataImportHandler = $this->createMock(DataImportHandler::class);
        $this->sessionConfigMock = $this->createMock(SessionConfig::class);


        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(YotiServiceInterface::class, $this->YotiServiceMock);
        $serviceManager->setService(DataQueryHandler::class, $this->dataQueryHandlerMock);
        $serviceManager->setService(SessionConfig::class, $this->sessionConfigMock);
        $serviceManager->setService(DataImportHandler::class, $this->dataImportHandler);
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

    public function testStatusWithID(): void
    {
        $this->dispatch('/counter-service/wuefhdfhaksjd/retrieve-status', 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(YotiController::class);
        $this->assertControllerClass('YotiController');
        $this->assertMatchedRouteName('retrieve_yoti_status');
    }

    public function testDetailsWithNouuid(): void
    {
        $this->dispatch('/counter-service/retrieve-status', 'GET');
        $this->assertResponseStatusCode(404);
    }

    /**
     * @throws \Exception
     */
    public function testBranchSearchNoPostCode(): void
    {
        $this->dispatchJSON('/counter-service/branches', 'POST', []);
        $this->assertResponseStatusCode(400);
    }

    /**
     * @throws \Exception
     */
    public function testBranchReturnFormat(): void
    {
        $response = '{"12345678":{"name":"St Neots","address":"35 High Street, St. Neots, Cambridgeshire, PE19 1NL"}' .
                    ',"12345675":{"name":"Hampstead","address":"66 High Street, Hampstead Heath, London, NW3 6LR"}}';
        $this->YotiServiceMock
            ->expects($this->once())->method('postOfficeBranch')
            ->with('NW1 4PG')
            ->willReturn($this->branchesArray());

        $this->dispatchJSON('/counter-service/branches', 'POST', ['search_string' => 'NW1 4PG']);
        $this->assertResponseStatusCode(200);
        $this->assertEquals($response, $this->getResponse()->getContent());
        $this->assertModuleName('application');
        $this->assertControllerName(YotiController::class);
        $this->assertControllerClass('YotiController');
        $this->assertMatchedRouteName('find_postoffice_branches');
    }
    public function testSessionCreateSuccess(): void
    {
        $caseData = CaseData::fromArray([
            'id' => 'a9bc8ab8-389c-4367-8a9b-762ab3050999',
            'personType' => 'donor',
            'firstName' => '',
            'lastName' => '',
            'dob' => '',
            'lpas' => [],
            'address' => [],
        ]);
        $sessionData = $this->sessionConfig($caseData);
        $response = [];
        $response["status"] = 201;
        $response["data"] = [
            "client_session_token_ttl" => 2630012,
            "session_id" => "19eb9325-61ed-4089-88dc-5bbc659443d3",
            "client_session_token" => "1c9f8e92-3a04-463e-9dd1-98dad2b657f2"
        ];

        $this->dataQueryHandlerMock
            ->expects($this->once())->method('getCaseByUUID')
            ->with('a9bc8ab8-389c-4367-8a9b-762ab3050999')
            ->willReturn($caseData);

        $this->sessionConfigMock
            ->expects($this->once())->method('build')
            ->with($caseData, 'override')
            ->willReturn($sessionData);

        $this->YotiServiceMock
            ->expects($this->once())->method('createSession')
            ->with($sessionData)
            ->willReturn($response);

        $this->dataImportHandler
            ->expects($this->exactly(2))->method('updateCaseData');

        $this->dispatchJSON(
            '/counter-service/a9bc8ab8-389c-4367-8a9b-762ab3050999/create-session?overrideToken=override',
            'POST'
        );
        $this->assertResponseStatusCode(201);
        $this->assertEquals(json_encode($response), $this->getResponse()->getContent());
        $this->assertModuleName('application');
        $this->assertControllerName(YotiController::class);
        $this->assertControllerClass('YotiController');
        $this->assertMatchedRouteName('create_yoti_session');
    }

    public function testSessionFailNoCase(): void
    {
        $caseData = null;

        $this->dataQueryHandlerMock
            ->expects($this->once())->method('getCaseByUUID')
            ->with('a9bc8ab8-389c-4367-8a9')
            ->willReturn($caseData);

        $this->dispatchJSON(
            '/counter-service/a9bc8ab8-389c-4367-8a9/create-session',
            'POST'
        );
        $this->assertResponseStatusCode(400);
        $this->assertModuleName('application');
        $this->assertControllerName(YotiController::class);
        $this->assertControllerClass('YotiController');
        $this->assertMatchedRouteName('create_yoti_session');
    }

    public function testPreparePdfFailWithNoSessionID(): void
    {
        $caseData = CaseData::fromArray([
            'id' => 'a9bc8ab8-389c-4367-8a9',
            'personType' => 'donor',
            'firstName' => '',
            'lastName' => '',
            'dob' => '',
            'lpas' => [],
            'address' => [],
            'sessionId' => null,
        ]);

        $this->dataQueryHandlerMock
            ->expects($this->once())->method('getCaseByUUID')
            ->with('a9bc8ab8-389c-4367-8a9')
            ->willReturn($caseData);

        $this->dispatchJSON(
            '/counter-service/a9bc8ab8-389c-4367-8a9/prepare-pdf',
            'GET'
        );
        $this->assertResponseStatusCode(400);
        $this->assertModuleName('application');
        $this->assertControllerName(YotiController::class);
        $this->assertControllerClass('YotiController');
        $this->assertMatchedRouteName('prepare_pdf_letter');

        $response = json_decode($this->getResponse()->getContent(), true);
        $this->assertEquals('SessionId does not exist to prepare PDF', $response['title']);
    }

    public function testPreparePdfSuccess(): void
    {
        $caseData = CaseData::fromArray([
            'id' => 'a9bc8ab8-389c-4367-8a9',
            'personType' => 'donor',
            'firstName' => '',
            'lastName' => '',
            'dob' => '',
            'lpas' => [],
            'address' => [],
            'sessionId' => '4367-8a9b-762ab3050999',
        ]);

        $response = [];
        $response["response"] = [
            "status" => "PDF Created"
        ];

        $this->dataQueryHandlerMock
            ->expects($this->once())->method('getCaseByUUID')
            ->with('a9bc8ab8-389c-4367-8a9')
            ->willReturn($caseData);

        $this->YotiServiceMock
            ->expects($this->once())->method('preparePDFLetter')
            ->with($caseData)
            ->willReturn($response);

        $this->dispatchJSON(
            '/counter-service/a9bc8ab8-389c-4367-8a9/prepare-pdf',
            'GET'
        );
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(YotiController::class);
        $this->assertControllerClass('YotiController');
        $this->assertMatchedRouteName('prepare_pdf_letter');
    }

    public function testRetrievePdfSuccess(): void
    {
        $caseData = CaseData::fromArray([
            'id' => 'a9bc8ab8-389c-4367-8a9',
            'personType' => 'donor',
            'firstName' => '',
            'lastName' => '',
            'dob' => '',
            'lpas' => [],
            'address' => [],
            'sessionId' => '4367-8a9b-762ab3050999',
        ]);

        $response = [];
        $response["pdfData"] = "pdfContents";

        $this->dataQueryHandlerMock
            ->expects($this->once())->method('getCaseByUUID')
            ->with('a9bc8ab8-389c-4367-8a9')
            ->willReturn($caseData);

        $this->YotiServiceMock
            ->expects($this->once())->method('retrieveLetterPDF')
            ->with($caseData)
            ->willReturn($response);

        $this->dispatchJSON(
            '/counter-service/a9bc8ab8-389c-4367-8a9/retrieve-letter',
            'GET'
        );
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(YotiController::class);
        $this->assertControllerClass('YotiController');
        $this->assertMatchedRouteName('retrieve_pdf_letter');
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

    public function branchesArray(): array
    {
        return [
            'branches' => [
                [
                    "type" => "UK_POST_OFFICE",
                    "fad_code" => "12345678",
                    "name" => "St Neots",
                    "address" => "35 High Street, St. Neots, Cambridgeshire",
                    "postcode" => "PE19 1NL",
                    "location" => [
                        "latitude" => 52.22864,
                        "longitude" => -0.26762
                    ]
                ],
                [
                    "type" => "UK_POST_OFFICE",
                    "fad_code" => "12345675",
                    "name" => "Hampstead",
                    "address" => "66 High Street, Hampstead Heath, London",
                    "postcode" => "NW3 6LR",
                    "location" => [
                        "latitude" => 52.22864,
                        "longitude" => -0.26762
                    ]
                ]
            ]
        ];
    }

    public function sessionConfig(CaseData $case): array
    {
        $sessionConfig = [];

        $sessionConfig["session_deadline"] = '2025-05-50 22:00:00';
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
}

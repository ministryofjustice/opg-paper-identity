<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Controller\YotiController;
use Application\Fixtures\DataImportHandler;
use Application\Fixtures\DataQueryHandler;
use Application\Model\Entity\CaseData;
use Application\Yoti\Http\Exception\YotiException;
use Application\Yoti\SessionConfig;
use Application\Yoti\SessionStatusService;
use Application\Yoti\YotiService;
use Application\Yoti\YotiServiceInterface;
use ApplicationTest\TestCase;
use Laminas\Http\Headers;
use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response;
use Laminas\Stdlib\ArrayUtils;
use PHPUnit\Framework\MockObject\MockObject;

class YotiControllerTest extends TestCase
{
    private YotiService&MockObject $YotiServiceMock;
    private SessionStatusService&MockObject $statusService;

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
        $this->statusService = $this->createMock(SessionStatusService::class);
        $this->dataQueryHandlerMock = $this->createMock(DataQueryHandler::class);
        $this->dataImportHandler = $this->createMock(DataImportHandler::class);
        $this->sessionConfigMock = $this->createMock(SessionConfig::class);


        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(YotiServiceInterface::class, $this->YotiServiceMock);
        $serviceManager->setService(DataQueryHandler::class, $this->dataQueryHandlerMock);
        $serviceManager->setService(SessionStatusService::class, $this->statusService);
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
        $caseData = CaseData::fromArray([
            'id' => '2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc',
            'personType' => 'donor',
            'firstName' => '',
            'lastName' => '',
            'yotiSessionId' => '2b45a8c1-dd35-47ef-a00e-c7b6264bf1dd',
            'address' => [],
            'counterService' => [
                'notificationsAuthToken' => ''
            ]
        ]);
        $this->dataQueryHandlerMock
            ->expects($this->once())
            ->method('getCaseByUUID')
            ->with('2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc')
            ->willReturn($caseData);

        $this->statusService
            ->expects($this->once())
            ->method('getSessionStatus')
            ->willReturn($caseData->counterService);

        $this->dispatch('/counter-service/2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc/retrieve-status', 'GET');
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
        $response = '{"12345678":{"name":"St Neots","address":"35 High Street, St. ' .
            'Neots, Cambridgeshire","post_code":"PE19 1NL"}' .
                    ',"12345675":{"name":"Hampstead","address":"66 High Street, ' .
            'Hampstead Heath, London","post_code":"NW3 6LR"}}';
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

    public function testSessionCreationStartsYotiProcess(): void
    {
        $uuid = 'test-uuid';
        $caseData = CaseData::fromArray([
            'id' => 'test-uuid',
            'firstName' => 'test',
            'lastName' => 'opg',
            'dob' => '1980-01-01',
            'address' => ['123 upper road'],
            'personType' => 'donor',
            'idMethod' => 'po_ukp',
            'counterService' => [
                'selectedPostOffice' => ''
            ]
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
        $pdfLetter = ["status" => "PDF Created", "pdfBase64" => "contents"];

        $this->dataQueryHandlerMock
            ->expects($this->atLeastOnce())->method('getCaseByUUID')
            ->with($uuid)
            ->willReturn($caseData);

        $this->sessionConfigMock
            ->expects($this->once())->method('build')
            ->with($caseData)
            ->willReturn($sessionData);

        $this->YotiServiceMock
            ->expects($this->once())->method('createSession')
            ->with($sessionData)
            ->willReturn($response);

        $this->YotiServiceMock
            ->expects($this->once())->method('preparePDFLetter')
            ->with($caseData)
            ->willReturn($pdfResponse);

        $this->YotiServiceMock
            ->expects($this->once())->method('retrieveLetterPDF')
            ->with($response["data"]["session_id"])
            ->willReturn($pdfLetter);

        $this->dataImportHandler
            ->expects($this->atLeast(1))->method('updateCaseData');

        $this->dataImportHandler
            ->expects($this->atLeast(1))->method('updateCaseChildAttribute');

        $this->dispatch('/counter-service/test-uuid/create-session', 'POST', []);
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(YotiController::class);
        $this->assertControllerClass('YotiController');
        $this->assertMatchedRouteName('create_yoti_session');
    }

    public function testYotiNotificationRequestFailsWithInvalidToken(): void
    {
        $response = '{"title":"Unauthorised request"}';
        $incorrectToken = 'incorrect';
        $bearerToken = 'b2b1508a-f418-49b4-ac01-c315e34cd15a';
        $this->dataQueryHandlerMock
            ->expects($this->once())->method('queryByYotiSessionId')
            ->with('18f8ecad-066f-4540-9c11-8fbd103ce935')
            ->willReturn(CaseData::fromArray([
                'id' => '2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc',
                'personType' => 'donor',
                'firstName' => '',
                'lastName' => '',
                'dob' => '',
                'lpas' => [],
                'address' => [],
                'counterService' => [
                    'notificationsAuthToken' => $bearerToken
                ]
            ]));

        $this->dataImportHandler
            ->expects($this->never())->method('updateCaseChildAttribute');

        $this->dispatchJSON(
            '/counter-service/notification',
            'POST',
            [
            'session_id' => '18f8ecad-066f-4540-9c11-8fbd103ce935',
            'topic' => 'first_branch_visit',

            ],
            'Bearer ' . $incorrectToken,
        );
        $this->assertResponseStatusCode(403);
        $this->assertEquals($response, $this->getResponse()->getContent());
        $this->assertModuleName('application');
        $this->assertControllerName(YotiController::class);
        $this->assertControllerClass('YotiController');
        $this->assertMatchedRouteName('yoti_notification');
    }

    public function testYotiNotificationCallsCaseUpdate(): void
    {
        $response = '{"Notification Status":"Updated"}';
        $bearerToken = 'b2b1508a-f418-49b4-ac01-c315e34cd15a';
        $this->dataQueryHandlerMock
            ->expects($this->once())->method('queryByYotiSessionId')
            ->with('18f8ecad-066f-4540-9c11-8fbd103ce935')
            ->willReturn(CaseData::fromArray([
                'id' => '2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc',
                'personType' => 'donor',
                'firstName' => '',
                'lastName' => '',
                'dob' => '',
                'lpas' => [],
                'address' => [],
                'counterService' => [
                    'notificationsAuthToken' => $bearerToken
                ]
            ]));

        $this->dataImportHandler
            ->expects($this->once())->method('updateCaseChildAttribute');

        $this->dispatchJSON(
            '/counter-service/notification',
            'POST',
            [
            'session_id' => '18f8ecad-066f-4540-9c11-8fbd103ce935',
            'topic' => 'first_branch_visit',

            ],
            'Bearer ' . $bearerToken,
        );
        $this->assertResponseStatusCode(200);
        $this->assertEquals($response, $this->getResponse()->getContent());
        $this->assertModuleName('application');
        $this->assertControllerName(YotiController::class);
        $this->assertControllerClass('YotiController');
        $this->assertMatchedRouteName('yoti_notification');
    }

    public function testYotiNotificationThrowsErrorForCaseNotFound(): void
    {
        $response = '{"title":"Case with session_id not found"}';
        $bearerToken = 'b2b1508a-f418-49b4-ac01-c315e34cd15a';
        $this->dataQueryHandlerMock
            ->expects($this->once())->method('queryByYotiSessionId')
            ->with('18f8ecad-066f-4540-9c11-8fbd103ce935')
            ->willReturn(null);

        $this->dataImportHandler
            ->expects($this->never())->method('updateCaseChildAttribute');

        $this->dispatchJSON(
            '/counter-service/notification',
            'POST',
            [
            'session_id' => '18f8ecad-066f-4540-9c11-8fbd103ce935',
            'topic' => 'first_branch_visit',
            ],
            'Bearer ' . $bearerToken,
        );
        $this->assertResponseStatusCode(500);
        $this->assertEquals($response, $this->getResponse()->getContent());
        $this->assertModuleName('application');
        $this->assertControllerName(YotiController::class);
        $this->assertControllerClass('YotiController');
        $this->assertMatchedRouteName('yoti_notification');
    }

    public function testYotiNotificationErrorWhenMissingParameters(): void
    {
        $response = '{"title":"Missing required parameters"}';
        $bearerToken = 'b2b1508a-f418-49b4-ac01-c315e34cd15a';
        $this->dataQueryHandlerMock
            ->expects($this->never())->method('queryByYotiSessionId');

        $this->dataImportHandler
            ->expects($this->never())->method('updateCaseData');

        $this->dispatchJSON(
            '/counter-service/notification',
            'POST',
            [
            'session_id' => '18f8ecad-066f-4540-9c11-8fbd103ce935',
            ],
            'Bearer ' . $bearerToken,
        );
        $this->assertResponseStatusCode(400);
        $this->assertEquals($response, $this->getResponse()->getContent());
        $this->assertModuleName('application');
        $this->assertControllerName(YotiController::class);
        $this->assertControllerClass('YotiController');
        $this->assertMatchedRouteName('yoti_notification');
    }

    public function dispatchJSON(string $path, string $method, mixed $data = null, string $authorize = null): void
    {
        $headers = new Headers();
        $headers->addHeaderLine('Accept', 'application/json');
        $headers->addHeaderLine('Content-Type', 'application/json');

        if ($authorize !== null) {
            $headers->addHeaderLine('authorization', $authorize);
        }
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
                    "post_code" => "PE19 1NL",
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
                    "post_code" => "NW3 6LR",
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
}

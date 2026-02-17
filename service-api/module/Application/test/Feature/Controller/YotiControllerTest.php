<?php

declare(strict_types=1);

namespace ApplicationTest\Feature\Controller;

use Application\Auth\Listener;
use Application\Controller\YotiController;
use Application\Enums\DocumentType;
use Application\Enums\IdRoute;
use Application\Enums\PersonType;
use Application\Fixtures\DataQueryHandler;
use Application\Fixtures\DataWriteHandler;
use Application\Model\Entity\CaseData;
use Application\Yoti\SessionConfig;
use Application\Yoti\SessionStatusService;
use Application\Yoti\YotiService;
use Application\Yoti\YotiServiceInterface;
use Laminas\Stdlib\ArrayUtils;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class YotiControllerTest extends BaseControllerTestCase
{
    private YotiService&MockObject $yotiServiceMock;
    private SessionStatusService&MockObject $statusService;
    private DataQueryHandler&MockObject $dataQueryHandlerMock;
    private DataWriteHandler&MockObject $dataHandler;
    private SessionConfig&MockObject $sessionConfigMock;
    private LoggerInterface&MockObject $logger;

    public function setUp(): void
    {
        $this->yotiServiceMock = $this->createMock(YotiService::class);
        $this->statusService = $this->createMock(SessionStatusService::class);
        $this->dataQueryHandlerMock = $this->createMock(DataQueryHandler::class);
        $this->dataHandler = $this->createMock(DataWriteHandler::class);
        $this->sessionConfigMock = $this->createMock(SessionConfig::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(YotiServiceInterface::class, $this->yotiServiceMock);
        $serviceManager->setService(DataQueryHandler::class, $this->dataQueryHandlerMock);
        $serviceManager->setService(SessionStatusService::class, $this->statusService);
        $serviceManager->setService(SessionConfig::class, $this->sessionConfigMock);
        $serviceManager->setService(DataWriteHandler::class, $this->dataHandler);
        $serviceManager->setService(LoggerInterface::class, $this->logger);

        // Disable authentication during tests
        $listener = $this->getApplicationServiceLocator()->get(Listener::class);
        $listener->detach($this->getApplication()->getEventManager());
    }

    public function testInvalidRouteDoesNotCrash(): void
    {
        $this->dispatch('/invalid/route', 'GET');
        $this->assertResponseStatusCode(404);
    }

    public function testStatusWithID(): void
    {
        $caseData = CaseData::fromArray([
            'id' => '2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc',
            'personType' => PersonType::Donor->value,
            'claimedIdentity' => [
                'firstName' => '',
                'lastName' => '',
                'address' => [],
            ],
            'yotiSessionId' => '2b45a8c1-dd35-47ef-a00e-c7b6264bf1dd',
            'counterService' => [
                'notificationsAuthToken' => '',
            ],
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
        $this->dispatch('/counter-service/branches', 'POST', []);
        $this->assertResponseStatusCode(400);
    }

    /**
     * @throws \Exception
     */
    public function testBranchReturnFormat(): void
    {
        $response = '{"12345678":{"fad_code":"12345678","name":"St Neots","address":"35 High Street, St. ' .
            'Neots, Cambridgeshire","post_code":"PE19 1NL"}' .
                    ',"12345675":{"fad_code":"12345675","name":"Hampstead","address":"66 High Street, ' .
            'Hampstead Heath, London","post_code":"NW3 6LR"}}';
        $this->yotiServiceMock
            ->expects($this->once())->method('postOfficeBranch')
            ->with('NW1 4PG')
            ->willReturn($this->branchesArray());

        $this->dispatch('/counter-service/branches', 'POST', ['search_string' => 'NW1 4PG']);
        $this->assertResponseStatusCode(200);
        $this->assertEquals($response, $this->getResponse()->getContent());
        $this->assertMatchedRouteName('find_postoffice_branches');
    }

    public function testSessionCreationStartsYotiProcess(): void
    {
        $uuid = 'test-uuid';
        $caseData = CaseData::fromArray([
            'id' => 'test-uuid',
            'claimedIdentity' => [
                'firstName' => 'test',
                'lastName' => 'opg',
                'dob' => '1980-01-01',
                'address' => [
                    'line1' => '123 upper road',
                ],
            ],
            'personType' => PersonType::Donor->value,
            "idMethod" => [
                'docType' => DocumentType::Passport->value,
                'idCountry' => "GBR",
                'idRoute' => IdRoute::KBV->value,
            ],
            'counterService' => [
                'selectedPostOffice' => '29348729',
            ],
        ]);

        $sessionData = $this->sessionConfig($caseData);
        $response = [];
        $response["status"] = 201;
        $response["data"] = [
            "client_session_token_ttl" => 2630012,
            "session_id" => "19eb9325-61ed-4089-88dc-5bbc659443d3",
            "client_session_token" => "1c9f8e92-3a04-463e-9dd1-98dad2b657f2",
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

        $this->dataHandler
            ->expects($this->once())->method('insertUpdateData');

        $this->dispatch('/counter-service/test-uuid/create-session', 'POST', []);
        $this->assertResponseStatusCode(200);
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
                'personType' => PersonType::Donor->value,
                'claimedIdentity' => [
                    'firstName' => '',
                    'lastName' => '',
                    'dob' => '',
                    'address' => [],
                ],
                'lpas' => [],
                'counterService' => [
                    'notificationsAuthToken' => $bearerToken,
                ],
            ]));

        $this->dataHandler
            ->expects($this->never())->method('updateCaseData');

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Unauthorized notification for case: 2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc: first_branch_visit');

        $this->dispatch(
            '/counter-service/notification',
            'POST',
            [
            'session_id' => '18f8ecad-066f-4540-9c11-8fbd103ce935',
            'topic' => 'first_branch_visit',

            ],
            ['Authorization' => 'Bearer ' . $incorrectToken],
        );
        $this->assertResponseStatusCode(403);
        $this->assertEquals($response, $this->getResponse()->getContent());
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
                'personType' => PersonType::Donor->value,
                'claimedIdentity' => [
                    'firstName' => '',
                    'lastName' => '',
                    'dob' => '',
                    'address' => [],
                ],
                'lpas' => [],
                'counterService' => [
                    'notificationsAuthToken' => $bearerToken,
                ],
            ]));

        $this->dataHandler
            ->expects($this->once())->method('updateCaseData')
            ->with(
                '2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc',
                'counterService.notificationState',
                'first_branch_visit',
            );

        $this->dispatch(
            '/counter-service/notification',
            'POST',
            [
            'session_id' => '18f8ecad-066f-4540-9c11-8fbd103ce935',
            'topic' => 'first_branch_visit',

            ],
            ['Authorization' => 'Bearer ' . $bearerToken],
        );
        $this->assertResponseStatusCode(200);
        $this->assertEquals($response, $this->getResponse()->getContent());
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

        $this->dataHandler
            ->expects($this->never())->method('updateCaseData');

        $this->dispatch(
            '/counter-service/notification',
            'POST',
            [
            'session_id' => '18f8ecad-066f-4540-9c11-8fbd103ce935',
            'topic' => 'first_branch_visit',
            ],
            ['Authorization' => 'Bearer ' . $bearerToken],
        );
        $this->assertResponseStatusCode(500);
        $this->assertEquals($response, $this->getResponse()->getContent());
        $this->assertMatchedRouteName('yoti_notification');
    }

    public function testYotiNotificationErrorWhenMissingParameters(): void
    {
        $response = '{"title":"Missing required parameters"}';
        $bearerToken = 'b2b1508a-f418-49b4-ac01-c315e34cd15a';
        $this->dataQueryHandlerMock
            ->expects($this->never())->method('queryByYotiSessionId');

        $this->dataHandler
            ->expects($this->never())->method('updateCaseData');

        $this->dispatch(
            '/counter-service/notification',
            'POST',
            [
            'session_id' => '18f8ecad-066f-4540-9c11-8fbd103ce935',
            ],
            ['Authorization' => 'Bearer ' . $bearerToken],
        );
        $this->assertResponseStatusCode(400);
        $this->assertEquals($response, $this->getResponse()->getContent());
        $this->assertMatchedRouteName('yoti_notification');
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
                        "longitude" => -0.26762,
                    ],
                ],
                [
                    "type" => "UK_POST_OFFICE",
                    "fad_code" => "12345675",
                    "name" => "Hampstead",
                    "address" => "66 High Street, Hampstead Heath, London",
                    "post_code" => "NW3 6LR",
                    "location" => [
                        "latitude" => 52.22864,
                        "longitude" => -0.26762,
                    ],
                ],
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
                    "manual_check" => "IBV",
                ],
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
                            "document_types" => [DocumentType::Passport->value],
                        ],
                    ],
                ],
            ],
        ];

        $sessionConfig["resources"] = [
            "applicant_profile" => [
                "given_names" => $case->claimedIdentity?->firstName,
                "family_name" => $case->claimedIdentity?->lastName,
                "date_of_birth" => $case->claimedIdentity?->dob,
                "structured_postal_address" => [],
            ],
        ];

        return $sessionConfig;
    }
}

<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Controller\YotiController;
use Application\Fixtures\DataImportHandler;
use Application\Fixtures\DataQueryHandler;
use Application\Model\Entity\CaseData;
use Application\Yoti\SessionConfig;
use Application\Yoti\YotiService;
use Application\Yoti\YotiServiceInterface;
use ApplicationTest\TestCase;
use Laminas\Http\Headers;
use Laminas\Http\Request as HttpRequest;
use Laminas\Stdlib\ArrayUtils;
use PHPUnit\Framework\MockObject\MockObject;

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
        $this->YotiServiceMock
            ->expects($this->once())
            ->method('retrieveResults')
            ->willReturn(['state' => 'test']);

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
        $response = '{"12345678":{"name":"St Neots","address":"35 High Street, St. ' .
            'Neots, Cambridgeshire","postcode":"PE19 1NL"}' .
                    ',"12345675":{"name":"Hampstead","address":"66 High Street, ' .
            'Hampstead Heath, London","postcode":"NW3 6LR"}}';
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
                    'notificationsAuthToken' => $bearerToken,
                ],
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
                    'notificationsAuthToken' => $bearerToken,
                ],
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
        $this->assertResponseStatusCode(400);
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
                    "postcode" => "PE19 1NL",
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
                    "postcode" => "NW3 6LR",
                    "location" => [
                        "latitude" => 52.22864,
                        "longitude" => -0.26762,
                    ],
                ],
            ],
        ];
    }
}

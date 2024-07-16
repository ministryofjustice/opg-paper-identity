<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Controller\YotiController;
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


        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(YotiServiceInterface::class, $this->YotiServiceMock);
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
}

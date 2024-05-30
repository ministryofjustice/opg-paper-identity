<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Controller\YotiController;
use Application\Yoti\YotiService;
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
        $serviceManager->setService(YotiService::class, $this->YotiServiceMock);
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

    public function testDetailsWithNoSession(): void
    {
        $response = '{"error":"Missing sessionId"}';
        $this->dispatch('/counter-service/retrieve-status', 'GET');
        $this->assertResponseStatusCode(400);
        $this->assertEquals($response, $this->getResponse()->getContent());
        $this->assertModuleName('application');
        $this->assertControllerName(YotiController::class);
        $this->assertControllerClass('YotiController');
        $this->assertMatchedRouteName('retrieve_yoti_status');
    }
}

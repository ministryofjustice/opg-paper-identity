<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Services\OpgApiService;
use Application\Services\SiriusApiService;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

/**
 * @psalm-import-type Lpa from SiriusApiService
 */
class HealthCheckControllerTest extends AbstractHttpControllerTestCase
{
    public function setUp(): void
    {
        $this->setApplicationConfig(include __DIR__ . '/../../../../config/application.config.php');

        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
    }

    public function testHealthCheckAction(): void
    {
        $this->dispatch('/health-check', 'GET');
        $this->assertResponseStatusCode(200);
    }

    public function testHealthCheckServiceAction(): void
    {
        $siriusApiService = $this->createMock(SiriusApiService::class);
        $opgApiService = $this->createMock(OpgApiService::class);

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setService(SiriusApiService::class, $siriusApiService);
        $serviceManager->setService(OpgApiService::class, $opgApiService);

        $siriusApiService->expects($this->once())
            ->method('checkAuth')
            ->willReturn(true);

        $opgApiService->expects($this->once())
            ->method('healthCheck')

            ->willReturn(true);

        $this->dispatch('/health-check-service', 'GET');
        $this->assertResponseStatusCode(200);
    }
}

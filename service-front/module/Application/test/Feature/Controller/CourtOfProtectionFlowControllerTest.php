<?php

declare(strict_types=1);

namespace ApplicationTest\Feature\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\CourtOfProtectionFlowController;
use Application\Services\SiriusApiService;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class CourtOfProtectionFlowControllerTest extends AbstractHttpControllerTestCase
{
    private OpgApiServiceInterface&MockObject $opgApiServiceMock;
    private SiriusApiService&MockObject $siriusApiServiceMock;
    private string $uuid;

    public function setUp(): void
    {
        $this->setApplicationConfig(include __DIR__ . '/../../../../../config/application.config.php');

        $this->uuid = '123e4567-e89b-12d3-a456-426614174000';

        $this->opgApiServiceMock = $this->createMock(OpgApiServiceInterface::class);
        $this->siriusApiServiceMock = $this->createMock(SiriusApiService::class);

        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(OpgApiServiceInterface::class, $this->opgApiServiceMock);
        $serviceManager->setService(SiriusApiService::class, $this->siriusApiServiceMock);
    }

    private function returnOpgResponseData(): array
    {
        return [
            "caseProgress" => [
                "fraudScore" => ["decision" => "STOP"]
            ],
            "lpas" => ["LP-12345"]
        ];
    }

    private function returnSiriusLpaResponse(): array
    {
        return [
            "opg.poas.lpastore" => [
                "donor" => [
                    "firstNames" => "John",
                    "lastName" => "Doe",
                ],
                "lpaType" => "property-and-affairs" // Ensuring this value is set to prevent null issues
            ]
        ];
    }

    public function testRegisterActionLoadsSuccessfully(): void
    {
        $mockResponseData = $this->returnOpgResponseData();

        $this->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseData);

        $mockSiriusLpaData = $this->returnSiriusLpaResponse();
        $this->siriusApiServiceMock
            ->expects(self::once())
            ->method('getLpaByUid')
            ->willReturn($mockSiriusLpaData);

        $this->dispatch("/{$this->uuid}/court-of-protection", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(CourtOfProtectionFlowController::class);
        $this->assertControllerClass('CourtOfProtectionFlowController');
        $this->assertMatchedRouteName('root/court_of_protection');
    }

    public function testRegisterActionRedirectsOnValidPost(): void
    {
        $mockResponseData = $this->returnOpgResponseData();

        $this->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseData);

        $mockSiriusLpaData = $this->returnSiriusLpaResponse();
        $this->siriusApiServiceMock
            ->expects(self::once())
            ->method('getLpaByUid')
            ->willReturn($mockSiriusLpaData);

        $this->opgApiServiceMock
            ->expects(self::once())
            ->method('startCourtOfProtection')
            ->with($this->uuid);

        $this->dispatch("/{$this->uuid}/court-of-protection", 'POST', ['confirmation' => true]);
        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo("/{$this->uuid}/court-of-protection-what-next");
    }

    public function testWhatNextActionLoadsSuccessfully(): void
    {
        $mockResponseData = $this->returnOpgResponseData();

        $this->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseData);

        $this->dispatch("/{$this->uuid}/court-of-protection-what-next", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(CourtOfProtectionFlowController::class);
        $this->assertControllerClass('CourtOfProtectionFlowController');
        $this->assertMatchedRouteName('root/court_of_protection_what_next');
    }
}

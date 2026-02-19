<?php

declare(strict_types=1);

namespace ApplicationTest\Feature\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Helpers\SendSiriusNoteHelper;
use Application\Helpers\SiriusDataProcessorHelper;
use Application\Services\SiriusApiService;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\RequestInterface;

class CourtOfProtectionFlowControllerTest extends BaseControllerTestCase
{
    private OpgApiServiceInterface&MockObject $opgApiServiceMock;
    private SiriusApiService&MockObject $siriusApiServiceMock;
    private SendSiriusNoteHelper&MockObject $sendSiriusNoteMock;
    private SiriusDataProcessorHelper&MockObject $siriusDataProcessorHelper;
    private string $uuid;

    public function setUp(): void
    {
        $this->uuid = '123e4567-e89b-12d3-a456-426614174000';

        $this->opgApiServiceMock = $this->createMock(OpgApiServiceInterface::class);
        $this->siriusApiServiceMock = $this->createMock(SiriusApiService::class);
        $this->sendSiriusNoteMock = $this->createMock(SendSiriusNoteHelper::class);
        $this->siriusDataProcessorHelper = $this->createMock(SiriusDataProcessorHelper::class);

        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(OpgApiServiceInterface::class, $this->opgApiServiceMock);
        $serviceManager->setService(SiriusApiService::class, $this->siriusApiServiceMock);
        $serviceManager->setService(SendSiriusNoteHelper::class, $this->sendSiriusNoteMock);
        $serviceManager->setService(SiriusDataProcessorHelper::class, $this->siriusDataProcessorHelper);
    }

    private function returnOpgResponseData(): array
    {
        return [
            "caseProgress" => [
                "fraudScore" => ["decision" => "STOP"],
            ],
            "lpas" => ["LP-12345"],
        ];
    }

    private function returnMockLpaArray(): array
    {
        return [
            "M-0000-0000-0000" => [
                "name" => "John Doe",
                "type" => "property-and-affairs",
            ],
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

        $this->siriusDataProcessorHelper
            ->expects(self::once())
            ->method('createLpaDetailsArray')
            ->willReturn($this->returnMockLpaArray());

        $this->dispatch("/{$this->uuid}/court-of-protection", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertMatchedRouteName('court_of_protection');
    }

    public function testRegisterActionRedirectsOnValidPost(): void
    {
        $mockResponseData = $this->returnOpgResponseData();

        $this->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseData);

        $this->opgApiServiceMock
            ->expects(self::once())
            ->method('sendIdentityCheck')
            ->with($this->uuid);

        $this->sendSiriusNoteMock
            ->expects(self::once())
            ->method('sendBlockedRoutesNote')
            ->with($mockResponseData, $this->isInstanceOf(RequestInterface::class));

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
        $this->assertMatchedRouteName('court_of_protection_what_next');
    }
}

<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\CPFlowController;
use Application\Services\FormProcessorService;
use Application\Services\SiriusApiService;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class CPFlowControllerTest extends AbstractHttpControllerTestCase
{
    private OpgApiServiceInterface&MockObject $opgApiServiceMock;
    private SiriusApiService&MockObject $siriusApiService;
    private FormProcessorService&MockObject $formProcessorService;
    private string $uuid;

    public function setUp(): void
    {
        $this->setApplicationConfig(include __DIR__ . '/../../../../config/application.config.php');

        $this->uuid = '49895f88-501b-4491-8381-e8aeeaef177d';

        $this->opgApiServiceMock = $this->createMock(OpgApiServiceInterface::class);
        $this->siriusApiService = $this->createMock(SiriusApiService::class);
        $this->formProcessorService = $this->createMock(FormProcessorService::class);

        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(OpgApiServiceInterface::class, $this->opgApiServiceMock);
        $serviceManager->setService(SiriusApiService::class, $this->siriusApiService);
        $serviceManager->setService(FormProcessorService::class, $this->formProcessorService);
    }

    public function testCPIdCheckReturnsPageWithData(): void
    {
        $mockResponseDataIdDetails = [
            "FirstName" => "Mary Anne",
            "LastName" => "Chapman",
            "DOB" => "01 May 1943",
            "Address" => "Address line 1, line 2, Country, BN1 4OD",
            "Role" => "cp",
            "LPA" => [
                "PA M-1234-ABCB-XXXX",
                "PW M-1234-ABCD-AAAA"
            ]
        ];

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/cp/how-will-cp-confirm", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(CpFlowController::class); // as specified in router's controller name alias
        $this->assertControllerClass('CpFlowController');
        $this->assertMatchedRouteName('cp_how_cp_confirms');
    }

    public function testNameMatchesIDPageWithData(): void
    {
        $mockResponseDataIdDetails = [
            "Name" => "Mary Anne Chapman",
            "DOB" => "01 May 1943",
            "Address" => "Address line 1, line 2, Country, BN1 4OD",
            "Role" => "cp",
            "LPA" => [
                "PA M-1234-ABCB-XXXX",
                "PW M-1234-ABCD-AAAA"
            ]
        ];

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/cp/does-name-match-id", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(CpFlowController::class); // as specified in router's controller name alias
        $this->assertControllerClass('CpFlowController');
        $this->assertMatchedRouteName('cp_does_name_match_id');
    }

    public function testConfirmLpasPageWithData(): void
    {
        $mockResponseDataIdDetails = [
            "FirstName" => "Mary Anne",
            "LastName" => "Chapman",
            "DOB" => "01 May 1943",
            "Address" => [
                "1 Court Street",
                "London",
                "UK",
                "SW1B 1BB",
            ],
            "Role" => "cp",
            "LPA" => [
                "PA M-XYXY-YAGA-35G3",
                "PW M-XYXY-YAGA-35G4"
            ]
        ];

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $mockLpasData = [
            [
                'lpa_ref' => 'PW PA M-XYXY-YAGA-35G3',
                'donor_name' => 'Mary Anne Chapman'
            ],
            [
                'lpa_ref' => 'PW M-VGAS-OAGA-34G9',
                'donor_name' => 'Mary Anne Chapman'
            ]
        ];

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getLpasByDonorData')
            ->willReturn($mockLpasData);

        $this->dispatch("/$this->uuid/cp/confirm-lpas", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(CpFlowController::class); // as specified in router's controller name alias
        $this->assertControllerClass('CpFlowController');
        $this->assertMatchedRouteName('cp_confirm_lpas');
    }
}

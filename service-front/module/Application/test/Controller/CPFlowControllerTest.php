<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\CPFlowController;
use Application\Helpers\FormProcessorHelper;
use Application\Services\SiriusApiService;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class CPFlowControllerTest extends AbstractHttpControllerTestCase
{
    private OpgApiServiceInterface&MockObject $opgApiServiceMock;
    private SiriusApiService&MockObject $siriusApiService;
    private FormProcessorHelper&MockObject $formProcessorService;
    private string $uuid;

    public function setUp(): void
    {
        $this->setApplicationConfig(include __DIR__ . '/../../../../config/application.config.php');

        $this->uuid = '49895f88-501b-4491-8381-e8aeeaef177d';

        $this->opgApiServiceMock = $this->createMock(OpgApiServiceInterface::class);
        $this->siriusApiService = $this->createMock(SiriusApiService::class);
        $this->formProcessorService = $this->createMock(FormProcessorHelper::class);

        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(OpgApiServiceInterface::class, $this->opgApiServiceMock);
        $serviceManager->setService(SiriusApiService::class, $this->siriusApiService);
        $serviceManager->setService(FormProcessorHelper::class, $this->formProcessorService);
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
        $this->assertMatchedRouteName('root/cp_how_cp_confirms');
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

        $this->dispatch("/$this->uuid/cp/name-match-check", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(CpFlowController::class); // as specified in router's controller name alias
        $this->assertControllerClass('CpFlowController');
        $this->assertMatchedRouteName('root/cp_name_match_check');
    }

    public function testConfirmLpasPageWithData(): void
    {
        $mockResponseDataIdDetails = [
            "id" => "2d86bb9d-d9ce-47a6-8447-4c160acaee6e",
            "personType" => "certificateProvider",
            "firstName" => "Mary Anne",
            "lastName" => "Chapman",
            "dob" => "01 May 1943",
            "address" => [
                "1 Court Street",
                "London",
                "UK",
                "SW1B 1BB",
            ],
            "lpas" => [
                "M-XYXY-YAGA-35G3",
                "M-XYXY-YAGA-35G4"
            ],
            "documentComplete" => false,
            "alternateAddress" => [
            ],
            "selectedPostOfficeDeadline" => null,
            "selectedPostOffice" => null,
            "searchPostcode" => null,
            "idMethod" => "nin"
        ];

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/cp/confirm-lpas", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(CpFlowController::class); // as specified in router's controller name alias
        $this->assertControllerClass('CpFlowController');
        $this->assertMatchedRouteName('root/cp_confirm_lpas');
    }

    public function testConfirmDobPageWithData(): void
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

        $this->dispatch("/$this->uuid/cp/confirm-dob", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(CpFlowController::class); // as specified in router's controller name alias
        $this->assertControllerClass('CpFlowController');
        $this->assertMatchedRouteName('root/cp_confirm_dob');
    }

    public function testConfirmAddressPageWithData(): void
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

        $this->dispatch("/$this->uuid/cp/confirm-address", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(CpFlowController::class); // as specified in router's controller name alias
        $this->assertControllerClass('CpFlowController');
        $this->assertMatchedRouteName('root/cp_confirm_address');
    }
}

<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\DocumentCheckController;
use Application\Helpers\FormProcessorHelper;
use Application\Helpers\SiriusDataProcessorHelper;
use Application\Helpers\DTO\FormProcessorResponseDto;
use Application\Services\SiriusApiService;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class DocumentCheckControllerTest extends AbstractHttpControllerTestCase
{
    private OpgApiServiceInterface&MockObject $opgApiServiceMock;
    private SiriusApiService&MockObject $siriusApiService;
    private FormProcessorHelper&MockObject $formProcessorService;
    private SiriusDataProcessorHelper&MockObject $siriusDataProcessorHelperMock;
    private string $uuid;

    public function setUp(): void
    {
        $this->setApplicationConfig(include __DIR__ . '/../../../../config/application.config.php');

        $this->uuid = '49895f88-501b-4491-8381-e8aeeaef177d';

        $this->opgApiServiceMock = $this->createMock(OpgApiServiceInterface::class);
        $this->siriusApiService = $this->createMock(SiriusApiService::class);
        $this->formProcessorService = $this->createMock(FormProcessorHelper::class);
        $this->siriusDataProcessorHelperMock = $this->createMock(SiriusDataProcessorHelper::class);

        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(OpgApiServiceInterface::class, $this->opgApiServiceMock);
        $serviceManager->setService(SiriusApiService::class, $this->siriusApiService);
        $serviceManager->setService(FormProcessorHelper::class, $this->formProcessorService);
        $serviceManager->setService(SiriusDataProcessorHelper::class, $this->siriusDataProcessorHelperMock);
    }

    public function testNationalInsuranceNumberReturnsPageWithData(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $mockServiceResponse = $this->returnServiceAvailabilityResponseData();
        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getServiceAvailability')
            ->willReturn($mockServiceResponse);

        $this->dispatch("/$this->uuid/national-insurance-number", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(DocumentCheckController::class);
        $this->assertControllerClass('DocumentCheckController');
        $this->assertMatchedRouteName('root/national_insurance_number');
        $this->assertQueryContentContains('p[id=nino_fullname]', 'Mary Anne Chapman');
        $this->assertQueryContentContains('p[id=nino_dob]', '01 May 1943');
    }

    /**
     * @dataProvider ninoData
     */
    public function testNationalInsuranceNumberReturnsPagePost(string $validity): void
    {
        $mockProcessed = $this->createMock(FormProcessorResponseDto::class);

        $mockResponseDataIdDetails = $this->returnOpgResponseData();
        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $mockServiceResponse = $this->returnServiceAvailabilityResponseData();
        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getServiceAvailability')
            ->willReturn($mockServiceResponse);

        $this
            ->formProcessorService
            ->expects(self::once())
            ->method('processNationalInsuranceNumberForm')
            ->willReturn($mockProcessed);

        $mockProcessed
            ->expects($this->exactly(2))
            ->method('getVariables')
            ->willReturn(["validity" => $validity]);

        if ($validity === "PASS") {
            $this
                ->formProcessorService
                ->expects(self::once())
                ->method('processTemplate');
        }

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('updateCaseSetDocumentComplete')
            ->with($this->uuid, 'NATIONAL_INSURANCE_NUMBER');

        $this->dispatch("/$this->uuid/national-insurance-number", 'POST', [
            'nino' => 'NP 11 22 33 C',
        ]);
    }

    public function ninoData(): array
    {
        return [
            ["PASS"],
            ["FAIL"]
        ];
    }

    public function testDrivingLicenceNumberReturnsPageWithData(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $mockServiceResponse = $this->returnServiceAvailabilityResponseData();
        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getServiceAvailability')
            ->willReturn($mockServiceResponse);

        $this->dispatch("/$this->uuid/driving-licence-number", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(DocumentCheckController::class);
        $this->assertControllerClass('DocumentCheckController');
        $this->assertMatchedRouteName('root/driving_licence_number');
    }

    public function returnOpgResponseData(): array
    {
        return [
            "id" => "2d86bb9d-d9ce-47a6-8447-4c160acaee6e",
            "personType" => "donor",
            "firstName" => "Mary Anne",
            "lastName" => "Chapman",
            "dob" => "1943-05-01",
            "address" => [
                "1 Court Street",
                "London",
                "UK",
                "SW1B 1BB",
            ],
            "lpas" => [
                "M-XYXY-YAGA-35G3",
            ],
            "documentComplete" => false,
            "alternateAddress" => [
            ],
            "selectedPostOffice" => null,
            "idMethodIncludingNation" => [
                "id_country" => "GBR",
                "id_method" => "DRIVING_LICENCE",
                'id_route' => 'TELEPHONE'
            ]
        ];
    }

    public function returnServiceAvailabilityResponseData(): array
    {
        return [
            'data' => [
                'PASSPORT' => false,
                'DRIVING_LICENCE' => false,
                'NATIONAL_INSURANCE_NUMBER' => false,
                'POST_OFFICE' => true,
                'VOUCHING' => true,
                'COURT_OF_PROTECTION' => true,
                'EXPERIAN' => false,
            ],
            'messages' => []
        ];
    }
}

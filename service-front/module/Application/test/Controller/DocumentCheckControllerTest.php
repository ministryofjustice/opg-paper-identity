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
    public function testNationalInsuranceNumberPagePost(string $validity): void
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
                ->method('processTemplate')
                ->willReturn('application\/pages\/national_insurance_number_success');
        }
        if ($validity === 'PASS') {
            $this
                ->opgApiServiceMock
                ->expects(self::once())
                ->method('updateCaseSetDocumentComplete')
                ->with($this->uuid, 'NATIONAL_INSURANCE_NUMBER');
        }

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

    /**
     * @dataProvider ninoErrorsData
     */
    public function testNationalInsuranceNumberErrors(array $post): void
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

        $this->dispatch("/$this->uuid/national-insurance-number", 'POST', $post);
        $this->assertQuery('p#nino-error');
    }

    public function ninoErrorsData(): array
    {
        return [
            'empty_form' => [
                []
            ],
            'wrong format' => [
                ['nino' => 'not a nino']
            ],
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

    /**
     * @dataProvider drivingLicenceData
     */
    public function testDrivingLicenceNumberPagePost(string $validity): void
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
            ->method('processDrivingLicenceForm')
            ->willReturn($mockProcessed);

        $mockProcessed
            ->expects($this->exactly(2))
            ->method('getVariables')
            ->willReturn(["validity" => $validity]);

        if ($validity === "PASS") {
            $this
                ->formProcessorService
                ->expects(self::once())
                ->method('processTemplate')
                ->willReturn('application\/pages\/driving_licence_number_success');
        }

        if ($validity === 'PASS') {
            $this
                ->opgApiServiceMock
                ->expects(self::once())
                ->method('updateCaseSetDocumentComplete')
                ->with($this->uuid, 'DRIVING_LICENCE');
        }

        $this->dispatch("/$this->uuid/driving-licence-number", 'POST', [
            'dln' => 'MORGA657054SM9IJ',
            'inDate' => 'yes',
        ]);
    }

    public function drivingLicenceData(): array
    {
        return [
            ["PASS"],
            ["FAIL"]
        ];
    }

    /**
     * @dataProvider dlnErrorsData
     */
    public function testDrivingLicenceNumberErrors(array $post, array $expectedErrors): void
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

        $this->dispatch("/$this->uuid/driving-licence-number", 'POST', $post);

        foreach ($expectedErrors as $error) {
            $this->assertQuery($error);
        }
    }

    public function dlnErrorsData(): array
    {
        return [
            'empty form' => [
                [],
                ['p#dln-error', 'p#inDate-error']
            ],
            'both invalid' => [
                ['dln' => '2345', 'inDate' => 'no'],
                ['p#dln-error', 'p#inDate-error']
            ],
            'valid dln,  invalid inDate' => [
                ['dln' => 'MORGA657054SM9IJ', 'inDate' => 'no'],
                ['p#inDate-error']
            ],
            'invalid dln, valid inDate' => [
                ['dln' => '1234', 'inDate' => 'yes'],
                ['p#dln-error']
            ],
        ];
    }

    public function testPassportNumberReturnsPageWithData(): void
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

        $this->dispatch("/$this->uuid/passport-number", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(DocumentCheckController::class);
        $this->assertControllerClass('DocumentCheckController');
        $this->assertMatchedRouteName('root/passport_number');
        $this->assertQueryContentContains('p[id=passport_fullname]', 'Mary Anne Chapman');
        $this->assertQueryContentContains('p[id=passport_dob]', '01 May 1943');
    }

    /**
     * @dataProvider passportNumberData
     */
    public function testPassportNumberPost(string $validity): void
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
            ->method('processPassportForm')
            ->willReturn($mockProcessed);

        $mockProcessed
            ->expects($this->exactly(2))
            ->method('getVariables')
            ->willReturn(['validity' => $validity]);

        if ($validity === "PASS") {
            $this
                ->formProcessorService
                ->expects(self::once())
                ->method('processTemplate')
                ->willReturn('application\/pages\/passport_number_success');
        }

        if ($validity === 'PASS') {
            $this
                ->opgApiServiceMock
                ->expects(self::once())
                ->method('updateCaseSetDocumentComplete')
                ->with($this->uuid, 'PASSPORT');
        }

        $this->dispatch("/$this->uuid/passport-number", 'POST', [
            'passport' => '123456785',
            'inDate' => 'yes',
        ]);
    }

    public function passportNumberData(): array
    {
        return [
            ['pass'],
            ['fail'],
        ];
    }

    public function testPassportNumberCheckDate(): void
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

        $this
            ->formProcessorService
            ->expects(self::once())
            ->method('processPassportDateForm');

        $this->dispatch("/$this->uuid/passport-number", 'POST', [
            'check_button' => '',
        ]);
    }

    /**
     * @dataProvider passportNumberErrors
     */
    public function testPassportNumberErrors(array $post, array $expectedErrors): void
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

        $this->dispatch("/$this->uuid/passport-number", 'POST', $post);

        foreach ($expectedErrors as $error) {
            $this->assertQuery($error);
        }
    }

    public function passportNumberErrors(): array
    {
        return [
            'empty form' => [
                [],
                ['p#passport-error', 'p#inDate-error']
            ],
            'both invalid' => [
                [
                    'passport' => 'invalid passport number',
                    'inDate' => 'no'
                ],
                ['p#passport-error', 'p#inDate-error']
            ],
            'valid passport, invalid inDate' => [
                [
                    'passport' => '123456785',
                    'inDate' => 'no'
                ],
                ['p#inDate-error']
            ],
            'invalid passport, valid inDate' => [
                [
                    'passport' => 'invalid passport number',
                    'inDate' => 'yes'
                ],
                ['p#passport-error']
            ],
        ];
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

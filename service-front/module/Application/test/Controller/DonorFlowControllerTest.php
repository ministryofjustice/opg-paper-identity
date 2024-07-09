<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\DonorFlowController;
use Application\Helpers\FormProcessorHelper;
use Application\Services\SiriusApiService;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class DonorFlowControllerTest extends AbstractHttpControllerTestCase
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

    public function testDonorIdCheckReturnsPageWithData(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/donor-id-check", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(DonorFlowController::class);
        $this->assertControllerClass('DonorFlowController');
        $this->assertMatchedRouteName('root/donor_id_check');
    }

    public function testAddressVerificationReturnsPageWithData(): void
    {
        $mockResponseDataAddressVerificationOptions = [
            'Passport',
            'Driving Licence',
            'National Insurance Number',
            'Voucher',
            'Post Office',
        ];

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getAddressVerificationData')
            ->willReturn($mockResponseDataAddressVerificationOptions);

        $mockResponseDataIdDetails = [
            "Name" => "Mary Anne Chapman",
            "DOB" => "01 May 1943",
            "Address" => "Address line 1, line 2, Country, BN1 4OD",
            "Role" => "donor",
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

        $this->dispatch("/$this->uuid/address_verification", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(DonorFlowController::class);
        $this->assertControllerClass('DonorFlowController');
        $this->assertMatchedRouteName('root/address_verification');
    }
    public function testLpasByDonorReturnsPageWithData(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/donor-lpa-check", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(DonorFlowController::class);
        $this->assertControllerClass('DonorFlowController');
        $this->assertMatchedRouteName('root/donor_lpa_check');
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

        $this->dispatch("/$this->uuid/national-insurance-number", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(DonorFlowController::class);
        $this->assertControllerClass('DonorFlowController');
        $this->assertMatchedRouteName('root/national_insurance_number');
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

        $this->dispatch("/$this->uuid/driving-licence-number", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(DonorFlowController::class);
        $this->assertControllerClass('DonorFlowController');
        $this->assertMatchedRouteName('root/driving_licence_number');
    }

    public function testHowWillDonorConfirmPage(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/how-will-donor-confirm", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(DonorFlowController::class);
        $this->assertControllerClass('DonorFlowController');
        $this->assertMatchedRouteName('root/how_donor_confirms');
    }

    public function testIdentityCheckPassedPage(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();
        $siriusResponse = $this->returnSiriusLpaResponse();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this
            ->siriusApiService
            ->expects(self::once())
            ->method('getLpaByUid')
            ->willReturn($siriusResponse);

        $this->dispatch("/$this->uuid/identity-check-passed", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(DonorFlowController::class);
        $this->assertControllerClass('DonorFlowController');
        $this->assertMatchedRouteName('root/identity_check_passed');
    }

    public function testIdentityCheckFailedPage(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();
        $siriusResponse = $this->returnSiriusLpaResponse();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this
            ->siriusApiService
            ->expects(self::once())
            ->method('getLpaByUid')
            ->willReturn($siriusResponse);

        $this->dispatch("/$this->uuid/identity-check-failed", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(DonorFlowController::class);
        $this->assertControllerClass('DonorFlowController');
        $this->assertMatchedRouteName('root/identity_check_failed');
    }
    public function testThinFileFailurePage(): void
    {
        $this->dispatch("/$this->uuid/thin-file-failure", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(DonorFlowController::class);
        $this->assertControllerClass('DonorFlowController');
        $this->assertMatchedRouteName('root/thin_file_failure');
    }

    public function testProvingIdentityPage(): void
    {
        $this->dispatch("/$this->uuid/proving-identity", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(DonorFlowController::class);
        $this->assertControllerClass('DonorFlowController');
        $this->assertMatchedRouteName('root/proving_identity');
    }

    public function testDonorIdMatchPage(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/donor-details-match-check", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(DonorFlowController::class);
        $this->assertControllerClass('DonorFlowController');
        $this->assertMatchedRouteName('root/donor_details_match_check');
    }


    public function returnOpgResponseData(): array
    {
        return [
            "id" => "2d86bb9d-d9ce-47a6-8447-4c160acaee6e",
            "personType" => "donor",
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
            ],
            "documentComplete" => false,
            "alternateAddress" => [
            ],
            "selectedPostOfficeDeadline" => null,
            "selectedPostOffice" => null,
            "searchPostcode" => null,
            "idMethod" => "nin"
        ];
    }

    public function returnSiriusLpaResponse(): array
    {
        return [
            "opg.poas.lpastore" => [
                "certificateProvider" => [
                    "address" => [
                        "line1" => "King House",
                        "line2" => "1 Victoria Street",
                        "line3" => "",
                        "town" => "London",
                        "postcode" => "SW1A 1BB",
                        "country" => "UK"
                    ],
                    "channel" => "paper",
                    "email" => "john.doe@gmail.com",
                    "firstNames" => "John",
                    "lastName" => "Doe",
                    "phone" => "07777 000000",
                    "signedAt" => "1938-11-08T07:10:43.0Z",
                    "uid" => "81e371b8-dda0-095f-4e7e-2bd936aec47c"
                ],
                "channel" => "paper",
                "donor" => [
                    "address" => [
                        "country" => "UK",
                        "line1" => "1 Street",
                        "line2" => "Road",
                        "postcode" => "SW1A 1AB",
                        "town" => "London"
                    ],
                    "contactLanguagePreference" => "cy",
                    "dateOfBirth" => "1982-08-13",
                    "email" => "joe.bloggs@gmail.com",
                    "firstNames" => "Joe",
                    "lastName" => "Bloggs",
                    "otherNamesKnownBy" => "Joseph Bloggs",
                    "uid" => "fa2eb929-92e8-78cf-aff6-e2c0811e3c60"
                ],
                "howReplacementAttorneysMakeDecisionsDetails" => "eu velit",
                "howReplacementAttorneysStepInDetails" => "mollit exercitation ipsum sunt enim",
                "lifeSustainingTreatmentOption" => "option-b",
                "lpaType" => "property-and-affairs",
                "registrationDate" => null,
                "signedAt" => "1912-08-24T01:13:49.0Z",
                "status" => "active",
                "uid" => "M-8VQ2-EY9I-DQ23",
                "updatedAt" => "1910-10-26T21:38:54.0Z",
                "whenTheLpaCanBeUsed" => "when-capacity-lost"
            ],
            "opg.poas.sirius" => [
                "donor" => [
                    "country" => "UK",
                    "dob" => "1982-08-13",
                    "firstname" => "Joe",
                    "postcode" => "SW1A 1AB",
                    "surname" => "Bloggs",
                    "town" => "London"
                ],
                "id" => 8223213,
                "uId" => "M-M1VL-PJ9D-IKUS"
            ]
        ];
    }
}

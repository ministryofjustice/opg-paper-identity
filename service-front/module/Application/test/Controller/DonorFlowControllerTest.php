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

    public function testLpasByDonorReturnsPageWithData(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();
        $mockSiriusData = $this->returnSiriusLpaResponse();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->willReturn($mockResponseDataIdDetails);


        $this
            ->siriusApiService
            ->expects(self::once())
            ->method('getLpaByUid')
            ->willReturn($mockSiriusData);

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
                        "country" => "TV",
                        "line1" => "93274 Goldner Club",
                        "line3" => "Oak Lawn",
                        "postcode" => "YG9 3RV",
                        "town" => "Caguas"
                    ],
                    "channel" => "paper",
                    "firstNames" => "Wilma",
                    "identityCheck" => [
                        "checkedAt" => "1940-11-01T22:28:42.0Z",
                        "type" => "one-login"
                    ],
                    "lastName" => "Lynch",
                    "phone" => "proident elit dolor cupidatat ut",
                    "signedAt" => "1967-02-10T08:53:14.0Z",
                    "uid" => "a72f52bd-1c26-e0ab-88a0-233e5611cd62"
                ],
                "channel" => "paper",
                "donor" => [
                    "address" => [
                        "country" => "TF",
                        "line1" => "9077 Bertrand Lane",
                        "line2" => "Grady Haven",
                        "line3" => "Hollywood",
                        "postcode" => "XW0 6ZQ"
                    ],
                    "contactLanguagePreference" => "en",
                    "dateOfBirth" => "1920-02-16",
                    "email" => "Bethany.Ritchie@yahoo.com",
                    "firstNames" => "Akeem",
                    "lastName" => "Wiegand",
                    "otherNamesKnownBy" => "Melba King",
                    "uid" => "d4c3d084-303a-3cd3-eab0-e981618b1fe8"
                ],
                "howAttorneysMakeDecisions" => "jointly-for-some-severally-for-others",
                "howReplacementAttorneysStepInDetails" => "in ut",
                "lpaType" => "property-and-affairs",
                "registrationDate" => "1938-06-30",
                "signedAt" => "1910-07-22T19:38:24.0Z",
                "status" => "registered",
                "uid" => "M-X7BG-VMAO-1V2F",
                "updatedAt" => "1906-03-13T01:06:58.0Z",
                "whenTheLpaCanBeUsed" => "when-capacity-lost"
            ],
            "opg.poas.sirius" => [
                "donor" => [
                    "addressLine2" => "Randi Trafficway",
                    "dob" => "1948-08-14",
                    "firstname" => "Isai",
                    "postcode" => "WR5 4XT",
                    "surname" => "Spencer",
                    "town" => "Galveston"
                ],
                "id" => 36902521,
                "uId" => "M-F4JG-7IHS-STS5"
            ]
        ];
    }
}

<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\PostOfficeFlowController;
use Application\Helpers\FormProcessorHelper;
use Application\Helpers\SiriusDataProcessorHelper;
use Application\PostOffice\Country;
use Application\PostOffice\DocumentType;
use Application\PostOffice\DocumentTypeRepository;
use Application\Services\SiriusApiService;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class PostOfficeDonorFlowControllerTest extends AbstractHttpControllerTestCase
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
    }

    public function returnOpgDetailsData(): array
    {
        return [
            "id" => "2d86bb9d-d9ce-47a6-8447-4c160acaee6e",
            "personType" => "certificateProvider",
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
            "searchPostcode" => null,
            "idMethod" => "nin",
            "yotiSessionId" => "00000000-0000-0000-0000-000000000000",
            "idMethodIncludingNation" => [
                "id_country" => "AUT",
                "id_method" => "DRIVING_LICENCE",
                'id_route' => 'POST_OFFICE'
            ]
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
                        "town" => "Caguas",
                    ],
                    "channel" => "paper",
                    "firstNames" => "Wilma",
                    "identityCheck" => [
                        "checkedAt" => "1940-11-01T22:28:42.0Z",
                        "type" => "one-login",
                    ],
                    "lastName" => "Lynch",
                    "phone" => "proident elit dolor cupidatat ut",
                    "signedAt" => "1967-02-10T08:53:14.0Z",
                    "uid" => "a72f52bd-1c26-e0ab-88a0-233e5611cd62",
                ],
                "channel" => "paper",
                "donor" => [
                    "address" => [
                        "country" => "TF",
                        "line1" => "9077 Bertrand Lane",
                        "line2" => "Grady Haven",
                        "line3" => "Hollywood",
                        "postcode" => "XW0 6ZQ",
                    ],
                    "contactLanguagePreference" => "en",
                    "dateOfBirth" => "1920-02-16",
                    "email" => "Bethany.Ritchie@yahoo.com",
                    "firstNames" => "Akeem",
                    "lastName" => "Wiegand",
                    "otherNamesKnownBy" => "Melba King",
                    "uid" => "d4c3d084-303a-3cd3-eab0-e981618b1fe8",
                ],
                "howAttorneysMakeDecisions" => "jointly-for-some-severally-for-others",
                "howReplacementAttorneysStepInDetails" => "in ut",
                "lpaType" => "property-and-affairs",
                "registrationDate" => "1938-06-30",
                "signedAt" => "1910-07-22T19:38:24.0Z",
                "status" => "registered",
                "uid" => "M-X7BG-VMAO-1V2F",
                "updatedAt" => "1906-03-13T01:06:58.0Z",
                "whenTheLpaCanBeUsed" => "when-capacity-lost",
            ],
            "opg.poas.sirius" => [
                "donor" => [
                    "addressLine2" => "Randi Trafficway",
                    "dob" => "1948-08-14",
                    "firstname" => "Isai",
                    "postcode" => "WR5 4XT",
                    "surname" => "Spencer",
                    "town" => "Galveston",
                ],
                "id" => 36902521,
                "uId" => "M-F4JG-7IHS-STS5",
            ],
        ];
    }

    public function testPostOfficeDocumentsPage(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgDetailsData();
        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/post-office-documents", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(PostOfficeFlowController::class);
        $this->assertControllerClass('PostOfficeFlowController');
        $this->assertMatchedRouteName('root/post_office_documents');
    }

    public function testWhatHappensNextPageWithData(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgDetailsData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/what-happens-next", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(PostOfficeFlowController::class);
        $this->assertControllerClass('PostOfficeFlowController');
        $this->assertMatchedRouteName('root/what_happens_next');
    }

    public function testNationalInsuranceNumberReturnsPageWithData(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgDetailsData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/post-office-route-not-available", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(PostOfficeFlowController::class);
        $this->assertControllerClass('PostOfficeFlowController');
        $this->assertMatchedRouteName('root/post_office_route_not_available');
    }

    public function testDonorMatchCheckPage(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgDetailsData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);


        $this
            ->siriusDataProcessorHelperMock
            ->expects(self::once())
            ->method('updatePaperIdCaseFromSirius');

        $this->dispatch("/$this->uuid/post-office-do-details-match", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(PostOfficeFlowController::class);
        $this->assertControllerClass('PostOfficeFlowController');
        $this->assertMatchedRouteName('root/po_do_details_match');
    }


    public function testDonorLpaCheckPage(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgDetailsData();
        $mockSiriusData = $this->returnSiriusLpaResponse();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this
            ->siriusApiService
            ->method('getLpaByUid')
            ->willReturn($mockSiriusData);

        $this->dispatch("/$this->uuid/post-office-donor-lpa-check", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(PostOfficeFlowController::class);
        $this->assertControllerClass('PostOfficeFlowController');
        $this->assertMatchedRouteName('root/po_donor_lpa_check');
    }

    public function testChooseCountryPage(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgDetailsData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/donor-choose-country", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(PostOfficeFlowController::class);
        $this->assertControllerClass('PostOfficeFlowController');
        $this->assertMatchedRouteName('root/donor_choose_country');

        $this->assertQueryContentContains('[name="id_country"] > option[value="AUT"]', 'Austria');
        $this->assertNotQuery('[name="id_country"] > option[value="GBR"]');
    }

    public function testPostOfficeCountriesIdPage(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgDetailsData();

        $documentTypeRepository = $this->createMock(DocumentTypeRepository::class);
        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(DocumentTypeRepository::class, $documentTypeRepository);

        $documentTypeRepository->expects($this->once())
            ->method('getByCountry')
            ->with(Country::AUT)
            ->willReturn([DocumentType::Passport, DocumentType::NationalId]);

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/donor-choose-country-id", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(PostOfficeFlowController::class);
        $this->assertControllerClass('PostOfficeFlowController');
        $this->assertMatchedRouteName('root/donor_choose_country_id');
    }

    public function testPostOfficeCountriesIdPageSubmit(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgDetailsData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('updateIdMethodWithCountry')
            ->with($this->uuid, ['id_method' => 'PASSPORT']);

        $this->dispatch("/$this->uuid/donor-choose-country-id", 'POST', ['id_method' => 'PASSPORT']);
        $this->assertResponseStatusCode(302);

        $this->assertRedirectTo(sprintf('/%s/donor-details-match-check', $this->uuid));
    }
}

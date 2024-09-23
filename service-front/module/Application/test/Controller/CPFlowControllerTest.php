<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\CPFlowController;
use Application\Helpers\FormProcessorHelper;
use Application\PostOffice\Country;
use Application\PostOffice\DocumentType;
use Application\PostOffice\DocumentTypeRepository;
use Application\Services\SiriusApiService;
use Laminas\Http\Request;
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

    public function returnOpgResponseData(): array
    {
        return [
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
            ],
            "documentComplete" => false,
            "alternateAddress" => [
            ],
            "selectedPostOffice" => null,
            "searchPostcode" => null,
            "idMethod" => "nin",
            "yotiSessionId" => "00000000-0000-0000-0000-000000000000",
            "idMethodIncludingNation" => [
                "country" => "AUT",
                "id_method" => "DRIVING_LICENCE"
            ]
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

    public function testCPIdCheckReturnsPageWithData(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

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
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

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
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $mockResponseDataSiriusLpa = $this->returnSiriusLpaResponse();

        $this
            ->siriusApiService
            ->expects(self::once())
            ->method('getLpaByUid')
            ->willReturn($mockResponseDataSiriusLpa);

        $this->dispatch("/$this->uuid/cp/confirm-lpas", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(CpFlowController::class); // as specified in router's controller name alias
        $this->assertControllerClass('CpFlowController');
        $this->assertMatchedRouteName('root/cp_confirm_lpas');
    }

    public function testConfirmDobPageWithData(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

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
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

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

    public function testPostOfficeDocumentsPage(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/cp/post-office-documents", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(CpFlowController::class); // as specified in router's controller name alias
        $this->assertControllerClass('CpFlowController');
        $this->assertMatchedRouteName('root/cp_post_office_documents');
    }

    public function testPostOfficeCountriesPage(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/cp/choose-country", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(CpFlowController::class); // as specified in router's controller name alias
        $this->assertControllerClass('CpFlowController');
        $this->assertMatchedRouteName('root/cp_choose_country');

        $this->assertQueryContentContains('[name="country"] > option[value="AUT"]', 'Austria');
        $this->assertNotQuery('[name="country"] > option[value="GBR"]');
    }

    public function testPostOfficeCountriesIdPage(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/cp/choose-country-id", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(CpFlowController::class); // as specified in router's controller name alias
        $this->assertControllerClass('CpFlowController');
        $this->assertMatchedRouteName('root/cp_choose_country_id');

        $response = $this->getResponse()->getContent();

        $this->assertStringContainsString('Choose document', $response);
    }

    public function testPostOfficeCountriesIdEmptyPostErrorPage(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $documentTypeRepository = $this->createMock(DocumentTypeRepository::class);
        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(DocumentTypeRepository::class, $documentTypeRepository);

        $documentTypeRepository->expects($this->once())
            ->method('getByCountry')
            ->with(Country::AUT)
            ->willReturn([DocumentType::Passport, DocumentType::NationalId]);

        $this->dispatch("/$this->uuid/cp/choose-country-id", 'POST', []);
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(CpFlowController::class); // as specified in router's controller name alias
        $this->assertControllerClass('CpFlowController');
        $this->assertMatchedRouteName('root/cp_choose_country_id');

        $response = $this->getResponse()->getContent();

        $this->assertStringContainsString('Please choose a type of document', $response);
    }

    public function testPostOfficeCountriesIdPostFailedValidationErrorPage(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch(
            "/$this->uuid/cp/choose-country-id",
            'POST',
            ['id_method' => 'PASSPOT']
        );
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(CpFlowController::class); // as specified in router's controller name alias
        $this->assertControllerClass('CpFlowController');
        $this->assertMatchedRouteName('root/cp_choose_country_id');

        $response = $this->getResponse()->getContent();

        $this->assertStringContainsString('This document code is not recognised', $response);
    }

    public function testPostOfficeCountriesIdPostPage(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

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

        $this->dispatch(
            "/$this->uuid/cp/choose-country-id",
            'POST',
            ['id_method' => 'PASSPORT']
        );
        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo(sprintf('/%s/cp/name-match-check', $this->uuid));
        $this->assertModuleName('application');
        $this->assertControllerName(CpFlowController::class); // as specified in router's controller name alias
        $this->assertControllerClass('CpFlowController');
        $this->assertMatchedRouteName('root/cp_choose_country_id');
    }
}

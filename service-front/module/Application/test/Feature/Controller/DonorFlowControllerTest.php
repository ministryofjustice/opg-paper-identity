<?php

declare(strict_types=1);

namespace ApplicationTest\Feature\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\DonorFlowController;
use Application\Enums\DocumentType;
use Application\Enums\IdRoute;
use Application\Helpers\FormProcessorHelper;
use Application\Helpers\SendSiriusNoteHelper;
use Application\Helpers\SiriusDataProcessorHelper;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\RequestInterface;

class DonorFlowControllerTest extends AbstractHttpControllerTestCase
{
    private OpgApiServiceInterface&MockObject $opgApiServiceMock;
    private FormProcessorHelper&MockObject $formProcessorService;
    private SendSiriusNoteHelper&MockObject $sendSiriusNoteMock;
    private SiriusDataProcessorHelper&MockObject $siriusDataProcessorHelperMock;
    private string $uuid;

    public function setUp(): void
    {
        $this->setApplicationConfig(include __DIR__ . '/../../../../../config/application.config.php');

        $this->uuid = '49895f88-501b-4491-8381-e8aeeaef177d';

        $this->opgApiServiceMock = $this->createMock(OpgApiServiceInterface::class);
        $this->formProcessorService = $this->createMock(FormProcessorHelper::class);
        $this->sendSiriusNoteMock = $this->createMock(SendSiriusNoteHelper::class);
        $this->siriusDataProcessorHelperMock = $this->createMock(SiriusDataProcessorHelper::class);

        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(OpgApiServiceInterface::class, $this->opgApiServiceMock);
        $serviceManager->setService(FormProcessorHelper::class, $this->formProcessorService);
        $serviceManager->setService(SendSiriusNoteHelper::class, $this->sendSiriusNoteMock);
        $serviceManager->setService(SiriusDataProcessorHelper::class, $this->siriusDataProcessorHelperMock);
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
            ->siriusDataProcessorHelperMock
            ->expects(self::once())
            ->method('createLpaDetailsArray')
            ->willReturn($mockSiriusData);

        $this->dispatch("/$this->uuid/donor-lpa-check", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(DonorFlowController::class);
        $this->assertControllerClass('DonorFlowController');
        $this->assertMatchedRouteName('root/donor_lpa_check');
    }

    public function testIdentityCheckPassedPage(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/identity-check-passed", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(DonorFlowController::class);
        $this->assertControllerClass('DonorFlowController');
        $this->assertMatchedRouteName('root/identity_check_passed');
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

    public function testDonorIdMatchPage(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

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

        $this->dispatch("/$this->uuid/donor-details-match-check", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(DonorFlowController::class);
        $this->assertControllerClass('DonorFlowController');
        $this->assertMatchedRouteName('root/donor_details_match_check');
    }

    #[DataProvider('donorDetailsMatchData')]
    public function testDonorDetailsMatchPageAdjustsContentCorrectly(array $detailsData, string $expectedContent): void
    {
        $detailsData = array_merge($this->returnOpgResponseData(), $detailsData);

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($detailsData);

        $this->dispatch("/$this->uuid/donor-details-match-check", 'GET');

        $this->assertQuery("p#{$expectedContent}");
    }

    public static function donorDetailsMatchData(): array
    {
        return [
            'not post-office route' => [
                [],
                'NOT_PO',
            ],
            'post office non UK driving-licence id' => [
                [
                    'idMethod' => [
                        'docType' => DocumentType::DrivingLicence->value,
                        'idCountry' => 'AUS',
                        'idRoute' => IdRoute::POST_OFFICE->value,
                    ],
                ],
                'PO_NON_GBR_DL',
            ],
            'post office UK driving licence' => [
                [
                    'idMethod' => [
                        'docType' => DocumentType::DrivingLicence->value,
                        'idCountry' => 'GBR',
                        'idRoute' => IdRoute::POST_OFFICE->value,
                    ],
                ],
                'PO_GBR_DL',
            ],
        ];
    }


    public function testWhatIsVouchingPage(): void
    {
        $this->dispatch("/$this->uuid/what-is-vouching");
        $this->assertModuleName('application');
        $this->assertControllerName(DonorFlowController::class);
        $this->assertControllerClass('DonorFlowController');
        $this->assertMatchedRouteName('root/what_is_vouching');
        $this->assertResponseStatusCode(200);
    }

    public function testWhatIsVouchingPageError(): void
    {
        $this->dispatch("/$this->uuid/what-is-vouching", "POST", []);
        $this->assertQuery("#chooseVouching-error");
    }

    public function testWhatIsVouchingPageOptNo(): void
    {
        $this->dispatch("/$this->uuid/what-is-vouching", 'POST', [
            'chooseVouching' => 'No',
        ]);
        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo("/$this->uuid/how-will-you-confirm");
    }

    public function testWhatIsVouchingPageOptYes(): void
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
            ->method('sendIdentityCheck')
            ->with($this->uuid);

        $this->sendSiriusNoteMock
            ->expects(self::once())
            ->method('sendBlockedRoutesNote')
            ->with($mockResponseDataIdDetails, $this->isInstanceOf(RequestInterface::class));

        $this->dispatch("/$this->uuid/what-is-vouching", 'POST', [
            'chooseVouching' => 'yes',
        ]);
        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo(sprintf('/%s/vouching-what-happens-next', $this->uuid));
    }

    public function testVouchingWhatHappensNextPage(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/vouching-what-happens-next", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(DonorFlowController::class);
        $this->assertControllerClass('DonorFlowController');
        $this->assertMatchedRouteName('root/vouching_what_happens_next');
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
            "idMethod" => [
                "idCountry" => "GBR",
                "docType" => DocumentType::DrivingLicence->value,
                'idRoute' => IdRoute::KBV->value,
            ],
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
}

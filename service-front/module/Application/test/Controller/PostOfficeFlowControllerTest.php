<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\PostOfficeFlowController;
use Application\Helpers\FormProcessorHelper;
use Application\Helpers\SiriusDataProcessorHelper;
use Application\Helpers\DTO\FormProcessorResponseDto;
use Application\PostOffice\Country;
use Application\PostOffice\DocumentType;
use Application\PostOffice\DocumentTypeRepository;
use Application\Services\SiriusApiService;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class PostOfficeFlowControllerTest extends AbstractHttpControllerTestCase
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

    public function returnOpgDetailsData(): array
    {
        return [
            "id" => "2d86bb9d-d9ce-47a6-8447-4c160acaee6e",
            "personType" => "donor",
            "firstName" => "Mary Anne",
            "lastName" => "Chapman",
            "dob" => "1943-05-01",
            "address" => [
                "line1" => "1 Court Street",
                "line2" => "",
                "town" => "London",
                "country" => "UK",
                "postcode" => "SW1B 1BB",
            ],
            "lpas" => [
                "M-0000-0000-0001",
                "M-0000-0000-0002",
            ],
            "documentComplete" => false,
            "alternateAddress" => [
            ],
            "counterService" => null,
            "idMethod" => "nin",
            "yotiSessionId" => "00000000-0000-0000-0000-000000000000",
            "idMethodIncludingNation" => [
                "id_country" => "AUT",
                "id_method" => "DRIVING_LICENCE",
                'id_route' => 'POST_OFFICE'
            ]
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

    /**
     * @dataProvider postOfficeDocumnentsRedirectData
     */
    public function testPostOfficeDocumentsRedirect(
        string $selectedOption,
        string $personType,
        string $expectedRedirect
    ): void {
        $mockResponseDataIdDetails = $this->returnOpgDetailsData();
        $mockResponseDataIdDetails["personType"] = $personType;
        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/post-office-documents", 'POST', [
            'id_method' => $selectedOption
        ]);
        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo("/$this->uuid/$expectedRedirect");
    }

    public function postOfficeDocumnentsRedirectData(): array
    {
        return [
            ['PASSPORT', 'donor', 'donor-details-match-check'],
            ['PASSPORT', 'certificateProvider', 'cp/name-match-check'],
            ['PASSPORT', 'voucher', 'vouching/voucher-name'],
            ['NONUKID', 'certificateProvider', 'po-choose-country'],
            ['NONUKID', 'voucher', 'po-choose-country'],
        ];
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

        $this->dispatch("/$this->uuid/post-office-what-happens-next", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(PostOfficeFlowController::class);
        $this->assertControllerClass('PostOfficeFlowController');
        $this->assertMatchedRouteName('root/po_what_happens_next');
    }

    public function testRouteNotAvailableData(): void
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

    public function testChooseCountryPage(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgDetailsData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/po-choose-country", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(PostOfficeFlowController::class);
        $this->assertControllerClass('PostOfficeFlowController');
        $this->assertMatchedRouteName('root/po_choose_country');

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

        $this->dispatch("/$this->uuid/po-choose-country-id", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(PostOfficeFlowController::class);
        $this->assertControllerClass('PostOfficeFlowController');
        $this->assertMatchedRouteName('root/po_choose_country_id');
    }

    public function testPostOfficeCountriesIdEmptyPostErrorPage(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgDetailsData();

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

        $this->dispatch("/$this->uuid/po-choose-country-id", 'POST', []);
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(PostOfficeFlowController::class); // as specified in router's controller name alias
        $this->assertControllerClass('PostOfficeFlowController');
        $this->assertMatchedRouteName('root/po_choose_country_id');

        $response = $this->getResponse()->getContent();

        $this->assertStringContainsString('Please choose a type of document', $response);
    }

    public function testPostOfficeCountriesIdPostFailedValidationErrorPage(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgDetailsData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch(
            "/$this->uuid/po-choose-country-id",
            'POST',
            ['id_method' => 'PASSPOT']
        );
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(PostOfficeFlowController::class); // as specified in router's controller name alias
        $this->assertControllerClass('PostOfficeFlowController');
        $this->assertMatchedRouteName('root/po_choose_country_id');

        $response = $this->getResponse()->getContent();

        $this->assertStringContainsString('This document code is not recognised', $response);
    }

    /**
     * @dataProvider postOfficeCountriesIdRedirectData
     */
    public function testPostOfficeCountriesIdPostPage(string $personType, string $expectedRedirect): void
    {
        $mockResponseDataIdDetails = $this->returnOpgDetailsData();
        $mockResponseDataIdDetails['personType'] = $personType;

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

        $this->dispatch("/$this->uuid/po-choose-country-id", 'POST', ['id_method' => 'PASSPORT']);
        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo("/{$this->uuid}/$expectedRedirect");
    }

    public function postOfficeCountriesIdRedirectData(): array
    {
        return [
            ['donor', 'donor-details-match-check'],
            ['certificateProvider', 'cp/name-match-check'],
            ['voucher', 'vouching/voucher-name'],
        ];
    }

    public function testfindPostOfficeBranchAction(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgDetailsData();
        $mockPostOfficeResponse = [
            '1234567' => [
                'name' => 'new post office',
                'address' => '1 Fake Street, Faketown',
                'post_code' => 'FA1 2KE'
            ],
            '7654321' => [
                'name' => 'old post office',
                'address' => '2 Pretend Road, Pretendcity',
                'post_code' => 'PR3 2TN'
            ],
        ];

        $postOfficesProcessed = [
            "jsonString1" => [
                "name" => "new post office",
                "address" => "1 Fake Street, Faketown",
                "post_code" => "FA1 2KE",
            ],
            "jsonString2" => [
                "name" => "old post office",
                "address" => "2 Pretend Road, Pretendcity",
                "post_code" => "PR3 2TN",
            ]
        ];

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('listPostOfficesByPostcode')
            ->with($this->uuid, 'SW1B 1BB')
            ->willReturn($mockPostOfficeResponse);

        $this
            ->formProcessorService
            ->expects(self::once())
            ->method("processPostOfficeSearchResponse")
            ->with($mockPostOfficeResponse)
            ->willReturn($postOfficesProcessed);

        $this->dispatch("/$this->uuid/find-post-office-branch", "GET");
        $this->assertQuery('input#jsonString1');
        $this->assertQuery('input#jsonString2');
    }

    public function testfindPostOfficeBranchSelectOption(): void
    {
        $mockProcessed = $this->createMock(FormProcessorResponseDto::class);

        $mockResponseDataIdDetails = $this->returnOpgDetailsData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this
            ->formProcessorService
            ->expects(self::once())
            ->method("processPostOfficeSelectForm")
            ->willReturn($mockProcessed);

        $mockProcessed
            ->expects($this->exactly(2))
            ->method('getRedirect')
            ->willReturn('root/confirm_post_office');

        $this->dispatch("/$this->uuid/find-post-office-branch", "POST");
        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo("/$this->uuid/confirm-post-office");
    }

    public function testfindPostOfficeBranchSearchPostcode(): void
    {
        $mockProcessed = $this->createMock(FormProcessorResponseDto::class);

        $mockResponseDataIdDetails = $this->returnOpgDetailsData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this
            ->formProcessorService
            ->expects(self::once())
            ->method("processPostOfficeSearchForm")
            ->willReturn($mockProcessed);

        $mockProcessed
            ->expects(self::once())
            ->method('getRedirect')
            ->willReturn(null);


        $mockProcessed
            ->expects(self::once())
            ->method('getVariables')
            ->willReturn([
                'post_office_list' => [
                    "somePostOffice" => "some post office"
                ]
            ]);

        $this->dispatch("/$this->uuid/find-post-office-branch", "POST", ["location" => "FA2 3KE"]);
        $this->assertQuery('input#somePostOffice');
    }


    /**
     * @dataProvider confirmPostOfficeData
     */
    public function testConfirmPostOfficeAction(string $dispatch): void
    {
        $mockResponseDataIdDetails = $this->returnOpgDetailsData();
        $mockResponseDataIdDetails['counterService'] = [
            'selectedPostOffice' => json_encode([
                'address' => 'post office, some town',
                'post_code' => 'PO1 0FC'
            ])
        ];

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this
            ->siriusApiService
            ->expects($this->exactly(2))
            ->method('getLpaByUid')
            ->willReturnCallback(fn (string $lpa) => match (true) {
                $lpa === 'M-0000-0000-0001' => [
                    'opg.poas.lpastore' => ['lpaType' => 'personal-welfare']
                ],
                $lpa === 'M-0000-0000-0002' => [
                    'opg.poas.sirius' => [
                        'caseSubtype' => 'property-and-affairs'
                    ],
                ],
            });

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('estimatePostofficeDeadline')
            ->with($this->uuid)
            ->willReturn('2025-01-20T10:56:18+00:00');

        if ($dispatch == "post") {
            $this
                ->opgApiServiceMock
                ->expects(self::once())
                ->method('confirmSelectedPostOffice')
                ->with($this->uuid, '20 Jan 2025');

            $this
                ->opgApiServiceMock
                ->expects(self::once())
                ->method('createYotiSession')
                ->with($this->uuid)
                ->willReturn(['pdfBase64' => 'base64forpdf']);

            $this
                ->siriusApiService
                ->expects(self::once())
                ->method('sendDocument')
                ->willReturn(['status' => 201]);
        }

        $this->dispatch("/$this->uuid/confirm-post-office", $dispatch);

        if ($dispatch === 'get') {
            $this->assertResponseStatusCode(200);
            $this->assertModuleName('application');
            $this->assertControllerName(PostOfficeFlowController::class);
            $this->assertControllerClass('PostOfficeFlowController');
            $this->assertMatchedRouteName('root/confirm_post_office');

            $this->assertQueryContentContains('span[id=lpaType]', 'PW');
            $this->assertQueryContentContains('span[id=lpaId]', 'M-0000-0000-0001');
            $this->assertQueryContentContains('span[id=lpaType]', 'PA');
            $this->assertQueryContentContains('span[id=lpaId]', 'M-0000-0000-0002');
            $this->assertQueryContentContains('dd[id=submissionDeadline]', '20 Jan 2025');
        } elseif ($dispatch === 'post') {
            $this->assertResponseStatusCode(302);
            $this->assertRedirectTo("/$this->uuid/post-office-what-happens-next");
        }
    }

    public function confirmPostOfficeData(): array
    {
        return [
            ['get'],
            ['post'],
        ];
    }
}

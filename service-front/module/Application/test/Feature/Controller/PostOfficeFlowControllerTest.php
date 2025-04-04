<?php

declare(strict_types=1);

namespace ApplicationTest\Feature\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\PostOfficeFlowController;
use Application\Enums\SiriusDocument;
use Application\Helpers\FormProcessorHelper;
use Application\Helpers\SiriusDataProcessorHelper;
use Application\PostOffice\Country as PostOfficeCountry;
use Application\PostOffice\DocumentType;
use Application\PostOffice\DocumentTypeRepository;
use Application\Services\SiriusApiService;
use Laminas\Http\Request;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class PostOfficeFlowControllerTest extends AbstractHttpControllerTestCase
{
    private OpgApiServiceInterface&MockObject $opgApiServiceMock;
    private SiriusApiService&MockObject $siriusApiService;
    private FormProcessorHelper&MockObject $formProcessorService;
    private SiriusDataProcessorHelper&MockObject $siriusDataProcessorHelperMock;
    private string $uuid;
    private static array $listPostOfficeResponse = [
            '1234567' => [
                'name' => 'new post office',
                'address' => '1 Fake Street, Faketown',
                'post_code' => 'FA1 2KE',
                'fad_code' => '1234567',
            ],
            '7654321' => [
                'name' => 'old post office',
                'address' => '2 Pretend Road, Pretendcity',
                'post_code' => 'PR3 2TN',
                'fad_code' => '7654321',
            ],
        ];

    public function setUp(): void
    {
        $this->setApplicationConfig(include __DIR__ . '/../../../../../config/application.config.php');

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

    public static function returnOpgDetailsData(): array
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
            "idMethod" => [
                "id_country" => "AUT",
                "doc_type" => "DRIVING_LICENCE",
                'id_route' => 'POST_OFFICE'
            ]
        ];
    }

    private function returnMockLpaArray(): array
    {
        return [
            "M-0000-0000-0001" => [
                "name" => "firstname surname",
                "type" => "PW"
            ],
            "M-0000-0000-0002" => [
                "name" => "another name",
                "type" => "PA"
            ]
        ];
    }

    public function getListPostofficeResponse(): array
    {
        return self::$listPostOfficeResponse;
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

    public static function postOfficeDocumnentsRedirectData(): array
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
            ->with(PostOfficeCountry::AUT)
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
            ->with(PostOfficeCountry::AUT)
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
            ->method('updateIdMethod')
            ->with($this->uuid, ['id_method' => 'PASSPORT']);

        $this->dispatch("/$this->uuid/po-choose-country-id", 'POST', ['id_method' => 'PASSPORT']);
        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo("/{$this->uuid}/$expectedRedirect");
    }

    public static function postOfficeCountriesIdRedirectData(): array
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
        $mockPostOfficeResponse = $this->getListPostofficeResponse();

        $poOne = json_encode($mockPostOfficeResponse['1234567']);
        $poTwo = json_encode($mockPostOfficeResponse['7654321']);

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

        $this->dispatch("/$this->uuid/find-post-office-branch", "GET");
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(PostOfficeFlowController::class);
        $this->assertControllerClass('PostOfficeFlowController');
        $this->assertMatchedRouteName('root/find_post_office_branch');
        $this->assertQuery("input#postoffice-1234567[value='$poOne']");
        $this->assertQueryContentContains('span#poAddress-1234567', '1 Fake Street, Faketown, FA1 2KE');
        $this->assertQuery("input#postoffice-7654321[value='$poTwo']");
        $this->assertQueryContentContains('span#poAddress-7654321', '2 Pretend Road, Pretendcity, PR3 2TN');
    }

    /**
     * @dataProvider selectPostOfficeData
     */
    public function testfindPostOfficeBranchSelect(
        array $post,
        bool $valid,
        ?array $idMethod,
        ?string $searchString,
        array $queries = [],
    ): void {
        $mockResponseDataIdDetails = $this->returnOpgDetailsData();
        if (isset($idMethod)) {
            $mockResponseDataIdDetails['idMethod'] = $idMethod;
        }

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        if ($valid) {
            $this
                ->opgApiServiceMock
                ->expects(self::once())
                ->method('addSelectedPostOffice')
                ->with($this->uuid, '1234567');

            $this
                ->siriusDataProcessorHelperMock
                ->expects($this->once())
                ->method('createLpaDetailsArray')
                ->willReturn($this->returnMockLpaArray());

            $this
                ->opgApiServiceMock
                ->expects(self::once())
                ->method('estimatePostofficeDeadline')
                ->with($this->uuid)
                ->willReturn('01 Jan 2025');
        } else {
            $this
                ->opgApiServiceMock
                ->expects(self::once())
                ->method('listPostOfficesByPostcode')
                ->with($this->uuid, $searchString ?? $mockResponseDataIdDetails['address']['postcode'])
                ->willReturn($this->getListPostofficeResponse());
        }

        $this->dispatch("/$this->uuid/find-post-office-branch", "POST", $post);

        foreach ($queries as $query) {
            $this->assertQueryContentContains(...$query);
        }
    }

    public static function selectPostOfficeData(): array
    {
        $validPost = [
            'selectPostoffice' => 'Continue',
            'postoffice' => json_encode(static::$listPostOfficeResponse['1234567']),
        ];

        $ukPassport = [
            'id_method' => 'PASSPORT',
            'id_country' => PostOfficeCountry::GBR->value
        ];

        return [
            'happy path render confirm page' => [
                $validPost, true, null, null,
                [
                    ['span#lpaType', 'PW'],
                    ['span#lpaType', 'PA'],
                    ['span#lpaId', 'M-0000-0000-0001'],
                    ['span#lpaId', 'M-0000-0000-0002'],
                    ['dd#name', 'Mary Anne Chapman'],
                    ['dd#dob', '01 May 1943'],
                    ['dd#submissionDeadline', '01 January 2025'],
                    ['dd#displayIdMethod', 'Photocard driving licence (Austria)'],
                    ['span#poAddressLine', '1 Fake Street'],
                    ['span#poAddressLine', 'Faketown'],
                    ['span#poAddressLine', 'FA1 2KE']
                ]
            ],
            'uk driving licence on confirm page' => [
                $validPost, true, $ukPassport, null,
                [
                    ['dd#displayIdMethod', 'UK Passport (current or expired in the last 18 months)'],
                ]
            ],
            'invalid, render find-post-office page with error' => [
                ['selectPostoffice' => 'Continue'], false, null, null,
                [
                    ['input#searchString[value="SW1B 1BB"]', ''],
                    ['span#postoffice-error', 'Please select an option']
                ]
            ],
            'invalid, but different searchString provided' => [
                [
                    'searchString' => 'somewhere',
                    'selectPostoffice' => 'Continue'
                ],
                false, null, 'somewhere',
                [
                    ['input#searchString[value="somewhere"]', ''],
                    ['span#postoffice-error', 'Please select an option']
                ]
            ]
        ];
    }

    /**
     * @dataProvider searchPostOfficeData
     */
    public function testfindPostOfficeBranchSearch(
        array $post,
        bool $valid,
        array $queries = [],
    ): void {
        $mockResponseDataIdDetails = $this->returnOpgDetailsData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);


        if ($valid) {
            $this
                ->opgApiServiceMock
                ->expects(self::once())
                ->method('listPostOfficesByPostcode')
                ->with($this->uuid, $post['searchString'])
                ->willReturn($this->getListPostofficeResponse());
        }

        $this->dispatch("/$this->uuid/find-post-office-branch", "POST", $post);

        foreach ($queries as $query) {
            $this->assertQueryContentContains(...$query);
        }

        if (! $valid) {
            // if the serach form is not valid then no post-offices are returned
            $this->assertNotQuery('input[name="postoffice"]');
        }
    }

    public static function searchPostOfficeData(): array
    {

        $poOne = json_encode(static::$listPostOfficeResponse['1234567']);
        $poTwo = json_encode(static::$listPostOfficeResponse['7654321']);

        return [
            'empty field shows error and no post-offices ' => [
                ['searchString' => ''],
                false,
                [
                    ['span#searchString-error', 'Please enter a postcode, town or street name']
                ],
            ],
            'search with a different searchString' => [
                ['searchString' => 'FakeTown'],
                true,
                [
                    ['input#searchString[value="FakeTown"]', ''],
                    ["input#postoffice-1234567[value='$poOne']", ''],
                    ['span#poAddress-1234567', '1 Fake Street, Faketown, FA1 2KE'],
                    ["input#postoffice-7654321[value='$poTwo']", ''],
                    ['span#poAddress-7654321', '2 Pretend Road, Pretendcity, PR3 2TN']
                ],
            ]
        ];
    }

    /**
     * @dataProvider confirmPostOfficeData
     */
    public function testfindPostOfficeBranchConfirm(string $personType, SiriusDocument $docType): void
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
            ->method('createYotiSession')
            ->with($this->uuid)
            ->willReturn(['pdfBase64' => 'pdf']);

        $this
            ->siriusApiService
            ->expects(self::once())
            ->method('sendDocument')
            // slightly clunky way of checking the arguments are passed correctly without checking `request`
            ->willReturnCallback(fn (
                array $caseDetails,
                SiriusDocument $systemType,
                Request $request,
                string $pdfSuffixBase64) => match (true) {
                    (
                        $caseDetails === $mockResponseDataIdDetails &&
                        $systemType === $docType &&
                        $pdfSuffixBase64 === 'pdf'
                    ) => ['status' => 201]
                });

        $this->dispatch("/$this->uuid/find-post-office-branch", "POST", [
            'confirmPostOffice' => 'Continue'
        ]);

        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo("/$this->uuid/post-office-what-happens-next");
    }

    public static function confirmPostOfficeData(): array
    {
        return [
            ['donor', SiriusDocument::PostOfficeDocCheckDonor],
            ['voucher', SiriusDocument::PostOfficeDocCheckVoucher],
        ];
    }
}

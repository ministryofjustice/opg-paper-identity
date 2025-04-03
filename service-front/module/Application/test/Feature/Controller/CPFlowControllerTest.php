<?php

declare(strict_types=1);

namespace ApplicationTest\Feature\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\CPFlowController;
use Application\Exceptions\PostcodeInvalidException;
use Application\Helpers\AddressProcessorHelper;
use Application\Helpers\FormProcessorHelper;
use Application\Helpers\SiriusDataProcessorHelper;
use Application\Services\SiriusApiService;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class CPFlowControllerTest extends AbstractHttpControllerTestCase
{
    private OpgApiServiceInterface&MockObject $opgApiServiceMock;
    private SiriusApiService&MockObject $siriusApiService;
    private FormProcessorHelper&MockObject $formProcessorService;
    private SiriusDataProcessorHelper&MockObject $siriusDataProcessorHelperMock;
    private string $uuid;

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

    public function returnOpgResponseData(): array
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
            "idMethod" => "nin",
            "yotiSessionId" => "00000000-0000-0000-0000-000000000000",
            "idMethodIncludingNation" => [
                "id_country" => "AUT",
                "id_method" => "DRIVING_LICENCE",
                'id_route' => 'POST_OFFICE'
            ]
        ];
    }

    private function returnMockLpaArray(): array
    {
        return [
            "M-0000-0000-0000" => [
                "name" => "John Doe",
                "type" => "property-and-affairs"
            ]
        ];
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

        $this
            ->siriusDataProcessorHelperMock
            ->expects(self::once())
            ->method('updatePaperIdCaseFromSirius')
            ->willReturn(null);

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

//        $mockResponseDataSiriusLpa = $this->returnSiriusLpaResponse();

        $this
            ->siriusDataProcessorHelperMock
            ->expects(self::once())
            ->method('createLpaDetailsArray')
            ->willReturn($this->returnMockLpaArray());

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

        /**
     * @dataProvider confirmAddressData
    */
    public function testConfirmAddressPageAdjustsContentCorrectly(array $detailsData, array $expectedContent): void
    {
        $detailsData = array_merge($this->returnOpgResponseData(), $detailsData);

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($detailsData);

        $this->dispatch("/$this->uuid/cp/confirm-address", 'GET');

        foreach ($expectedContent as $q) {
            $this->assertQuery($q);
        }
    }

    public static function confirmAddressData(): array
    {
        return [
            'not post-office route' => [
                [],
                ['p#NOT_PO']
            ],
            'post office non UK driving-licence id' => [
                [
                    'idRoute' => 'POST_OFFICE',
                    'idMethodIncludingNation' => [
                        'id_method' => 'DRIVING_LICENCE',
                        'id_country' => 'AUS',
                        'id_route' => 'POST_OFFICE'
                    ]
                ],
                ['p#PO_NON_GBR_DL']
            ],
            'post office UK driving licence' => [
                [
                    'idRoute' => 'POST_OFFICE',
                    'idMethodIncludingNation' => [
                        'id_method' => 'DRIVING_LICENCE',
                        'id_country' => 'GBR',
                        'id_route' => 'POST_OFFICE'
                    ]
                ],
                ['p#PO_GBR_DL']
            ]
        ];
    }

    public function testEnterPostcodeAddsValidationMessageWhenPostcodeInvalidExceptionThrown(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this
            ->siriusApiService
            ->expects(self::once())
            ->method('searchAddressesByPostcode')
            ->with('SW1A1AA', $this->isInstanceOf(\Laminas\Http\Request::class))
            ->willThrowException(new PostcodeInvalidException());

        $this->dispatch(
            sprintf('/%s/cp/enter-postcode', $this->uuid),
            'POST',
            ['postcode' => 'SW1A1AA']
        );

        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(CPFlowController::class);
        $this->assertControllerClass('CPFlowController');
        $this->assertMatchedRouteName('root/cp_enter_postcode');

        $response = $this->getResponse()->getContent();
        $this->assertStringContainsString(AddressProcessorHelper::ERROR_POSTCODE_NOT_FOUND, $response);
    }
}

<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\DonorPostOfficeFlowController;
use Application\Services\FormProcessorService;
use Application\Services\SiriusApiService;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class PostOfficeDonorFlowControllerTest extends AbstractHttpControllerTestCase
{
    private OpgApiServiceInterface&MockObject $opgApiServiceMock;
    private SiriusApiService&MockObject $siriusApiService;
    private FormProcessorService&MockObject $formProcessorService;
    private string $uuid;

    public function setUp(): void
    {
        $this->setApplicationConfig(include __DIR__ . '/../../../../config/application.config.php');

        $this->uuid = '49895f88-501b-4491-8381-e8aeeaef177d';

        $this->opgApiServiceMock = $this->createMock(OpgApiServiceInterface::class);
        $this->siriusApiService = $this->createMock(SiriusApiService::class);
        $this->formProcessorService = $this->createMock(FormProcessorService::class);

        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(OpgApiServiceInterface::class, $this->opgApiServiceMock);
        $serviceManager->setService(SiriusApiService::class, $this->siriusApiService);
        $serviceManager->setService(FormProcessorService::class, $this->formProcessorService);
    }

    public function testPstOfficeDocumentsPage(): void
    {
        $mockResponseDataIdDetails = [
            "firstName" => "Mary Anne",
            "lastName" => "Chapman",
            "dob" => "01 May 1943",
            "address" => [
                "1 Court Street",
                "London",
                "SW1B 1BB",
                "UK"
            ],
            "personType" => "Donor",
            "lpas" => [
                "PA M-1234-ABCB-XXXX",
                "PW M-1234-ABCD-AAAA"
            ],
            "idMethod" => "ukp",
            "id" => "3b992d91-27d6-4592-8680-ffd7d198c5bf",
        ];

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/post-office-documents", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(DonorPostOfficeFlowController::class); // as specified in router's controller name alias
        $this->assertControllerClass('DonorPostOfficeFlowController');
        $this->assertMatchedRouteName('post_office_documents');
    }

    public function testFindPostOfficePage(): void
    {
        $mockResponseDataIdDetails = [
            "firstName" => "Mary Anne",
            "lastName" => "Chapman",
            "dob" => "01 May 1943",
            "address" => [
                "1 Court Street",
                "London",
                "SW1B 1BB",
                "UK"
            ],
            "personType" => "Donor",
            "lpas" => [
                "PA M-1234-ABCB-XXXX",
                "PW M-1234-ABCD-AAAA"
            ],
            "idMethod" => "ukp",
            "id" => "3b992d91-27d6-4592-8680-ffd7d198c5bf",
        ];

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/find-post-office", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(DonorPostOfficeFlowController::class); // as specified in router's controller name alias
        $this->assertControllerClass('DonorPostOfficeFlowController');
        $this->assertMatchedRouteName('find_post_office');
    }
    public function testWhatHappensNextPageWithData(): void
    {
        $mockResponseDataIdDetails = [
            "firstName" => "Mary Anne",
            "lastName" => "Chapman",
            "dob" => "01 May 1943",
            "address" => [
                "1 Court Street",
                "London",
                "SW1B 1BB",
                "UK"
            ],
            "personType" => "Donor",
            "lpas" => [
                "PA M-1234-ABCB-XXXX",
                "PW M-1234-ABCD-AAAA"
            ],
            "idMethod" => "ukp",
            "id" => "3b992d91-27d6-4592-8680-ffd7d198c5bf",
        ];

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/what-happens-next", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(DonorPostOfficeFlowController::class); // as specified in router's controller name alias
        $this->assertControllerClass('DonorPostOfficeFlowController');
        $this->assertMatchedRouteName('what_happens_next');
    }

    public function testNationalInsuranceNumberReturnsPageWithData(): void
    {
        $mockResponseDataIdDetails = [
            "firstName" => "Mary Anne",
            "lastName" => "Chapman",
            "dob" => "01 May 1943",
            "address" => [
                "1 Court Street",
                "London",
                "SW1B 1BB",
                "UK"
            ],
            "personType" => "Donor",
            "lpas" => [
                "PA M-1234-ABCB-XXXX",
                "PW M-1234-ABCD-AAAA"
            ],
            "idMethod" => "ukp",
            "id" => "3b992d91-27d6-4592-8680-ffd7d198c5bf",
        ];

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/post-office-route-not-available", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(DonorPostOfficeFlowController::class); // as specified in router's controller name alias
        $this->assertControllerClass('DonorPostOfficeFlowController');
        $this->assertMatchedRouteName('post_office_route_not_available');
    }
}

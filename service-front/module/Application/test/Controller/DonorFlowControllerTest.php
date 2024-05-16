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
        $mockResponseDataIdDetails = [
            "Name" => "Mary Anne Chapman",
            "DOB" => "01 May 1943",
            "Address" => "Address line 1, line 2, Country, BN1 4OD",
            "Role" => "Donor",
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
            "Role" => "Donor",
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
        $mockResponseDataAddressVerificationOptions = [
            [
                'lpa_ref' => 'PW M-1234-ABCD-AAAA',
                'donor_name' => 'Mary Anne Chapman'
            ],
            [
                'lpa_ref' => 'PA M-1234-ABCD-XXXX',
                'donor_name' => 'Mary Anne Chapman'
            ]
        ];

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getLpasByDonorData')
            ->willReturn($mockResponseDataAddressVerificationOptions);

        $this->dispatch("/$this->uuid/donor-lpa-check", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(DonorFlowController::class);
        $this->assertControllerClass('DonorFlowController');
        $this->assertMatchedRouteName('root/donor_lpa_check');
    }

    public function testNationalInsuranceNumberReturnsPageWithData(): void
    {
        $mockResponseDataIdDetails = [
            "Name" => "Mary Anne Chapman",
            "DOB" => "01 May 1943",
            "Address" => "Address line 1, line 2, Country, BN1 4OD",
            "Role" => "Donor",
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

        $this->dispatch("/$this->uuid/national-insurance-number", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(DonorFlowController::class);
        $this->assertControllerClass('DonorFlowController');
        $this->assertMatchedRouteName('root/national_insurance_number');
    }

    public function testDrivingLicenceNumberReturnsPageWithData(): void
    {
        $mockResponseDataIdDetails = [
            "Name" => "Mary Anne Chapman",
            "DOB" => "01 May 1943",
            "Address" => "Address line 1, line 2, Country, BN1 4OD",
            "Role" => "Donor",
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

        $this->dispatch("/$this->uuid/driving-licence-number", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(DonorFlowController::class);
        $this->assertControllerClass('DonorFlowController');
        $this->assertMatchedRouteName('root/driving_licence_number');
    }

    public function testHowWillDonorConfirmPage(): void
    {
        $mockResponseDataIdDetails = [
            "Name" => "Mary Anne Chapman",
            "DOB" => "01 May 1943",
            "Address" => "Address line 1, line 2, Country, BN1 4OD",
            "Role" => "Donor",
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

        $this->dispatch("/$this->uuid/how-will-donor-confirm", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(DonorFlowController::class);
        $this->assertControllerClass('DonorFlowController');
        $this->assertMatchedRouteName('root/how_donor_confirms');
    }

    public function testIdentityCheckPassedPage(): void
    {
        $mockResponseDataIdDetails = [
            "Name" => "Mary Anne Chapman",
            "DOB" => "01 May 1943",
            "Address" => "Address line 1, line 2, Country, BN1 4OD",
            "Role" => "Donor",
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

        $mockResponseDataAddressVerificationOptions = [
            [
                'lpa_ref' => 'PW M-1234-ABCD-AAAA',
                'donor_name' => 'Mary Anne Chapman'
            ],
            [
                'lpa_ref' => 'PA M-1234-ABCD-XXXX',
                'donor_name' => 'Mary Anne Chapman'
            ]
        ];

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getLpasByDonorData')
            ->willReturn($mockResponseDataAddressVerificationOptions);

        $this->dispatch("/$this->uuid/identity-check-passed", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(DonorFlowController::class);
        $this->assertControllerClass('DonorFlowController');
        $this->assertMatchedRouteName('root/identity_check_passed');
    }

    public function testIdentityCheckFailedPage(): void
    {
        $mockResponseDataIdDetails = [
            "Name" => "Mary Anne Chapman",
            "DOB" => "01 May 1943",
            "Address" => "Address line 1, line 2, Country, BN1 4OD",
            "Role" => "Donor",
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

        $mockResponseDataAddressVerificationOptions = [
            [
                'lpa_ref' => 'PW M-1234-ABCD-AAAA',
                'donor_name' => 'Mary Anne Chapman'
            ],
            [
                'lpa_ref' => 'PA M-1234-ABCD-XXXX',
                'donor_name' => 'Mary Anne Chapman'
            ]
        ];

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getLpasByDonorData')
            ->willReturn($mockResponseDataAddressVerificationOptions);

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
        $mockResponseDataIdDetails = [
            "Name" => "Mary Anne Chapman",
            "dob" => "01 May 1943",
            "Address" => "Address line 1, line 2, Country, BN1 4OD",
            "Role" => "Donor",
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

        $this->dispatch("/$this->uuid/donor-details-match-check", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(DonorFlowController::class);
        $this->assertControllerClass('DonorFlowController');
        $this->assertMatchedRouteName('root/donor_details_match_check');
    }
}

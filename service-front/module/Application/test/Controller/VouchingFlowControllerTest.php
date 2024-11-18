<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\VouchingFlowController;
use Application\Helpers\DependencyCheck;
use Application\Helpers\FormProcessorHelper;
use Application\Helpers\VoucherMatchLpaActorHelper;
use Application\PostOffice\Country;
use Application\PostOffice\DocumentType;
use Application\PostOffice\DocumentTypeRepository;
use Application\Services\SiriusApiService;
use Application\Enums\LpaActorTypes;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class VouchingFlowControllerTest extends AbstractHttpControllerTestCase
{
    private OpgApiServiceInterface&MockObject $opgApiServiceMock;
    private SiriusApiService&MockObject $siriusApiService;
    private VoucherMatchLpaActorHelper&MockObject $voucherMatchMock;
    private string $uuid;

    public function setUp(): void
    {
        $this->setApplicationConfig(include __DIR__ . '/../../../../config/application.config.php');

        $this->uuid = '49895f88-501b-4491-8381-e8aeeaef177d';

        $this->opgApiServiceMock = $this->createMock(OpgApiServiceInterface::class);
        $this->siriusApiService = $this->createMock(SiriusApiService::class);
        $this->voucherMatchMock = $this->createMock(VoucherMatchLpaActorHelper::class);

        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(OpgApiServiceInterface::class, $this->opgApiServiceMock);
        $serviceManager->setService(SiriusApiService::class, $this->siriusApiService);
        $serviceManager->setService(VoucherMatchLpaActorHelper::class, $this->voucherMatchMock);
    }

    public function returnOpgResponseData(): array
    {
        return [
            "id" => "49895f88-501b-4491-8381-e8aeeaef177d",
            "personType" => "voucher",
            "firstName" => null,
            "lastName" => null,
            "dob" => null,
            "address" => [],
            "vouchingFor" => [
                "firstName" => "firstName",
                "lastName" => "lastName",
            ],
            "lpas" => [
                "M-XYXY-YAGA-35G3",
                "M-AAAA-1234-5678",
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

    public function testConfirmVouchingWithData(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/vouching/confirm-vouching", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(VouchingFlowController::class);
        $this->assertControllerClass('VouchingFlowController');
        $this->assertMatchedRouteName('root/confirm_vouching');
    }

    public function testConfirmVouchingWithError(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/vouching/confirm-vouching", 'POST', []);
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(VouchingFlowController::class);
        $this->assertControllerClass('VouchingFlowController');
        $this->assertMatchedRouteName('root/confirm_vouching');

        $response = $this->getResponse()->getContent();
        $this->assertStringContainsString('Confirm eligibility to continue', $response);
        $this->assertStringContainsString('Confirm declaration to continue', $response);
    }

    public function testConfirmVouchingWithSuccess(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/vouching/confirm-vouching", 'POST', [
            'eligibility' => "eligibility_confirmed",
            'declaration' => "declaration_confirmed"
        ]);
        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo(sprintf('/%s/vouching/confirm-vouching', $this->uuid));
    }

    public function testConfirmVouchingTryDifferentRoute(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/vouching/confirm-vouching", 'POST', [
            'tryDifferent' => "Try a different method",
        ]);
        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo("/start?personType=donor&lpas%5B%5D=M-XYXY-YAGA-35G3&lpas%5B%5D=M-AAAA-1234-5678");
    }

    public function testVoucherNamePage(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/vouching/voucher-name", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(VouchingFlowController::class);
        $this->assertControllerClass('VouchingFlowController');
        $this->assertMatchedRouteName('root/voucher_name');
    }

    public function testVoucherNameRedirect(): void
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
            ->expects($this->exactly(2))
            ->method("getLpaByUid")
            ->willReturnCallback(fn (string $lpa) => match (true) {
                $lpa === 'M-XYXY-YAGA-35G3' => ["lpaData" => "one"],
                $lpa === 'M-AAAA-1234-5678' => ["lpaData" => "two"],
            });

        $this
            ->voucherMatchMock
            ->expects($this->exactly(2))
            ->method("checkNameMatch")
            ->willReturnMap([
                ["firstName", "lastName", ["lpaData" => "one"], []],
                ["firstName", "lastName", ["lpaData" => "two"], []]
            ]);

        $this->dispatch("/$this->uuid/vouching/voucher-name", 'POST', [
            "firstName" => "firstName",
            "lastName" => "lastName",
        ]);
        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo(sprintf('/%s/vouching/voucher-name', $this->uuid));
    }

    public function testVoucherNameMatchWarning(): void
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
            ->expects($this->exactly(2))
            ->method("getLpaByUid")
            ->willReturnCallback(fn (string $lpa) => match (true) {
                $lpa === 'M-XYXY-YAGA-35G3' => ["lpaData" => "one"],
                $lpa === 'M-AAAA-1234-5678' => ["lpaData" => "two"],
            });

        $this
            ->voucherMatchMock
            ->expects($this->exactly(2))
            ->method("checkNameMatch")
            ->willReturnMap([
                ["firstName", "lastName", ["lpaData" => "one"], [LpaActorTypes::DONOR->value]],
                ["firstName", "lastName", ["lpaData" => "two"], []]
            ]);

        $this->dispatch("/$this->uuid/vouching/voucher-name", 'POST', [
            "firstName" => "firstName",
            "lastName" => "lastName",
        ]);
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains(
            'h2[name=donor_warning]',
            'The donor is also called firstName lastName. ' .
            'Confirm that these are two different people with the same name.'
        );
    }

    public function testVoucherNameMatchContinueAfterWarning(): void
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
            ->expects($this->exactly(2))
            ->method("getLpaByUid")
            ->willReturnCallback(fn (string $lpa) => match (true) {
                $lpa === 'M-XYXY-YAGA-35G3' => ["lpaData" => "one"],
                $lpa === 'M-AAAA-1234-5678' => ["lpaData" => "two"],
            });

        $this
            ->voucherMatchMock
            ->expects($this->exactly(2))
            ->method("checkNameMatch")
            ->willReturnMap([
                ["firstName", "lastName", ["lpaData" => "one"], [LpaActorTypes::DONOR->value]],
                ["firstName", "lastName", ["lpaData" => "two"], []]
            ]);

        $this->dispatch("/$this->uuid/vouching/voucher-name", 'POST', [
            "firstName" => "firstName",
            "lastName" => "lastName",
            "continue-after-warning" => "continue"
        ]);
        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo(sprintf('/%s/vouching/voucher-name', $this->uuid));
    }
}

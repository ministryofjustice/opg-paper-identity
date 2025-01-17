<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\VouchingFlowController;
use Application\Helpers\AddDonorFormHelper;
use Application\Helpers\VoucherMatchLpaActorHelper;
use Application\Services\SiriusApiService;
use Application\Enums\LpaActorTypes;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Application\Enums\IdMethod as IdMethodEnum;

class VouchingFlowControllerTest extends AbstractHttpControllerTestCase
{
    private OpgApiServiceInterface&MockObject $opgApiServiceMock;
    private SiriusApiService&MockObject $siriusApiServiceMock;
    private AddDonorFormHelper&MockObject $addDonorFormHelperMock;
    private VoucherMatchLpaActorHelper&MockObject $voucherMatchMock;
    private string $uuid;
    private array $routes;

    public function setUp(): void
    {
        $this->setApplicationConfig(include __DIR__ . '/../../../../config/application.config.php');

        $this->uuid = '49895f88-501b-4491-8381-e8aeeaef177d';
        $this->routes = [
            "confirm" => "vouching/confirm-vouching",
            "howConfirm" => "vouching/how-will-you-confirm",
            "name" => "vouching/voucher-name",
            "dob" => "vouching/voucher-dob",
            "postcode" => "vouching/enter-postcode",
            "selectAddress" => "vouching/select-address",
            "manualAddress" => "vouching/enter-address-manual",
            "confirmDonors" => "vouching/confirm-donors",
            "addDonor" => "vouching/add-donor",
        ];

        $this->opgApiServiceMock = $this->createMock(OpgApiServiceInterface::class);
        $this->siriusApiServiceMock = $this->createMock(SiriusApiService::class);
        $this->addDonorFormHelperMock = $this->createMock(AddDonorFormHelper::class);
        $this->voucherMatchMock = $this->createMock(VoucherMatchLpaActorHelper::class);

        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(OpgApiServiceInterface::class, $this->opgApiServiceMock);
        $serviceManager->setService(SiriusApiService::class, $this->siriusApiServiceMock);
        $serviceManager->setService(AddDonorFormHelper::class, $this->addDonorFormHelperMock);
        $serviceManager->setService(VoucherMatchLpaActorHelper::class, $this->voucherMatchMock);
    }

    public function getFakeAddress(): array
    {
        return [
            'line1' => '456 Pretend Road',
            'town' => 'Faketown',
            'postcode' => 'FA2 3KE',
            'country' => 'United Kingdom',
        ];
    }

    public function returnOpgResponseData(array $overwrite = []): array
    {
        $base = [
            "id" => "49895f88-501b-4491-8381-e8aeeaef177d",
            "personType" => "voucher",
            "firstName" => null,
            "lastName" => null,
            "dob" => null,
            "address" => null,
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
            "idMethod" => "nin",
            "yotiSessionId" => "00000000-0000-0000-0000-000000000000",
            "idMethodIncludingNation" => [
                "id_country" => "AUT",
                "id_method" => "DRIVING_LICENCE",
                'id_route' => 'POST_OFFICE'
            ]
        ];
        return array_merge($base, $overwrite);
    }

    public function returnServiceAvailability(): array
    {
        return [
            'data' => [
                'PASSPORT' => true,
                'DRIVING_LICENCE' => true,
                'NATIONAL_INSURANCE_NUMBER' => true,
                'POST_OFFICE' => true
            ],
            'messages' => []
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

        $this->dispatch("/$this->uuid/{$this->routes['confirm']}", 'GET');
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

        $this->dispatch("/$this->uuid/{$this->routes['confirm']}", 'POST', []);
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

        $this->dispatch("/$this->uuid/{$this->routes['confirm']}", 'POST', [
            'eligibility' => "eligibility_confirmed",
            'declaration' => "declaration_confirmed"
        ]);
        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo("/$this->uuid/{$this->routes['howConfirm']}");
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

        $this->dispatch("/$this->uuid/{$this->routes['confirm']}", 'POST', [
            'tryDifferent' => "Try a different method",
        ]);
        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo("/start?personType=donor&lpas%5B%5D=M-XYXY-YAGA-35G3&lpas%5B%5D=M-AAAA-1234-5678");
    }

    public function testHowWillYouConfirmRendersTemplateWithDefaults(): void
    {
        $mockResponseData = $this->returnOpgResponseData();
        $mockServiceAvailability = $this->returnServiceAvailability();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseData);

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getServiceAvailability')
            ->with($this->uuid)
            ->willReturn($mockServiceAvailability);

        $this->dispatch("/$this->uuid/{$this->routes['howConfirm']}", 'GET');

        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(VouchingFlowController::class);
        $this->assertControllerClass('VouchingFlowController');
        $this->assertMatchedRouteName('root/vouching_how_will_you_confirm');

        $response = $this->getResponse()->getContent();
        $this->assertStringContainsString('How will you confirm your identity?', $response);
        $this->assertStringContainsString('National insurance number', $response);
        $this->assertStringContainsString('UK Passport', $response);
        $this->assertStringContainsString('UK driving licence', $response);
        $this->assertStringContainsString('Post Office', $response);
    }

    public function testHowWillYouConfirmHandlesPostOfficeMethod(): void
    {
        $mockResponseData = $this->returnOpgResponseData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseData);

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('updateIdMethodWithCountry')
            ->with(
                $this->uuid,
                [
                    'id_route' => IdMethodEnum::PostOffice->value,
                ]
            );

        $this->dispatch("/$this->uuid/{$this->routes['howConfirm']}", 'POST', [
            'id_method' => IdMethodEnum::PostOffice->value,
        ]);

        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo("/$this->uuid/post-office-documents");
    }

    public function testHowWillYouConfirmHandlesTelephoneMethod(): void
    {
        $mockResponseData = $this->returnOpgResponseData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseData);

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('updateIdMethodWithCountry')
            ->with(
                $this->uuid,
                [
                    'id_route' => 'TELEPHONE',
                    'id_country' => 'GBR',
                    'id_method' => 'TELEPHONE',
                ]
            );

        $this->dispatch("/$this->uuid/{$this->routes['howConfirm']}", 'POST', [
            'id_method' => 'TELEPHONE',
        ]);

        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo("/$this->uuid/{$this->routes['name']}");
    }

    public function testHowWillYouConfirmNoOptionSelected(): void
    {
        $mockResponseData = $this->returnOpgResponseData();
        $mockServiceAvailability = $this->returnServiceAvailability();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseData);

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getServiceAvailability')
            ->with($this->uuid)
            ->willReturn($mockServiceAvailability);

        $this->dispatch("/$this->uuid/{$this->routes['howConfirm']}", 'POST', [
            'id_method' => null
        ]);

        $this->assertResponseStatusCode(200);
        $response = $this->getResponse()->getContent();
        $this->assertStringContainsString('How will you confirm your identity?', $response);
        $this->assertStringContainsString('Please select an option', $response);
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

        $this->dispatch("/$this->uuid/{$this->routes['name']}", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(VouchingFlowController::class);
        $this->assertControllerClass('VouchingFlowController');
        $this->assertMatchedRouteName('root/voucher_name');
    }

    public function testVoucherNamePreFilled(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData([
            "firstName" => "firstName",
            "lastName" => "lastName",
        ]);

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/{$this->routes['name']}", 'GET');
        $this->assertQuery('input#voucher-first-name[value=firstName]');
        $this->assertQuery('input#voucher-last-name[value=lastName]');
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
            ->siriusApiServiceMock
            ->expects($this->exactly(2))
            ->method("getLpaByUid")
            ->willReturnCallback(fn (string $lpa) => match (true) {
                $lpa === 'M-XYXY-YAGA-35G3' => ["lpaData" => "one"],
                $lpa === 'M-AAAA-1234-5678' => ["lpaData" => "two"],
            });

        $this
            ->voucherMatchMock
            ->expects($this->exactly(2))
            ->method("checkMatch")
            ->willReturnMap([
                [["lpaData" => "one"], "firstName", "lastName", null, false],
                [["lpaData" => "two"], "firstName", "lastName", null, false]
            ]);

        $this->dispatch("/$this->uuid/{$this->routes['name']}", 'POST', [
            "firstName" => "firstName",
            "lastName" => "lastName",
        ]);
        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo("/$this->uuid/{$this->routes['dob']}");
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
            ->siriusApiServiceMock
            ->expects($this->exactly(2))
            ->method("getLpaByUid")
            ->willReturnCallback(fn (string $lpa) => match (true) {
                $lpa === 'M-XYXY-YAGA-35G3' => ["lpaData" => "one"],
                $lpa === 'M-AAAA-1234-5678' => ["lpaData" => "two"],
            });

        $this
            ->voucherMatchMock
            ->expects($this->exactly(2))
            ->method("checkMatch")
            ->willReturnMap([
                [["lpaData" => "one"], "firstName", "lastName", null, false],
                [["lpaData" => "two"], "firstName", "lastName", null, [
                    "firstName" => "firstName",
                    "lastName" => "lastName",
                    "dob" => "dob",
                    "type" => LpaActorTypes::DONOR->value
                ]]
            ]);

        $this->dispatch("/$this->uuid/{$this->routes['name']}", 'POST', [
            "firstName" => "firstName",
            "lastName" => "lastName"
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
            ->siriusApiServiceMock
            ->expects($this->exactly(2))
            ->method("getLpaByUid")
            ->willReturnCallback(fn (string $lpa) => match (true) {
                $lpa === 'M-XYXY-YAGA-35G3' => ["lpaData" => "one"],
                $lpa === 'M-AAAA-1234-5678' => ["lpaData" => "two"],
            });

        $this
            ->voucherMatchMock
            ->expects($this->exactly(2))
            ->method("checkMatch")
            ->willReturnMap([
                [["lpaData" => "one"], "firstName", "lastName", null, false],
                [["lpaData" => "two"], "firstName", "lastName", null, [
                    "firstName" => "firstName",
                    "lastName" => "lastName",
                    "dob" => "dob",
                    "type" => LpaActorTypes::DONOR->value
                ]]
            ]);

        $this->dispatch("/$this->uuid/{$this->routes['name']}", 'POST', [
            "firstName" => "firstName",
            "lastName" => "lastName",
            "continue-after-warning" => "continue"
        ]);

        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo("/$this->uuid/{$this->routes['dob']}");
    }

    public function testVoucherDobPage(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/{$this->routes['dob']}", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(VouchingFlowController::class);
        $this->assertControllerClass('VouchingFlowController');
        $this->assertMatchedRouteName('root/voucher_dob');
    }

    public function testVoucherDobPreFilled(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData([
            "dob" => '1980-01-01'
        ]);

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/{$this->routes['dob']}", 'GET');
        $this->assertQuery('input#voucher-dob-day[value=01]');
        $this->assertQuery('input#voucher-dob-month[value=01]');
        $this->assertQuery('input#voucher-dob-year[value=1980]');
    }

    /**
     * @dataProvider voucherDobRedirectData
     */
    public function testVoucherDobRedirect(array $detailsData, string $expectedRedirect): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData($detailsData);

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this
            ->siriusApiServiceMock
            ->expects($this->exactly(2))
            ->method("getLpaByUid")
            ->willReturnCallback(fn (string $lpa) => match (true) {
                $lpa === 'M-XYXY-YAGA-35G3' => ["lpaData" => "one"],
                $lpa === 'M-AAAA-1234-5678' => ["lpaData" => "two"],
            });

        $this
            ->voucherMatchMock
            ->expects($this->exactly(2))
            ->method("checkMatch")
            ->willReturnMap([
                [["lpaData" => "one"], "firstName", "lastName", "1980-1-1", false],
                [["lpaData" => "two"], "firstName", "lastName", "1980-1-1", false]
            ]);

        $this->dispatch("/$this->uuid/{$this->routes['dob']}", 'POST', [
            "dob_day" => "1",
            "dob_month" => "1",
            "dob_year" => "1980"
        ]);

        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo("/$this->uuid/{$this->routes[$expectedRedirect]}");
    }

    public function voucherDobRedirectData(): array
    {
        return [
            [
                [
                    "firstName" => "firstName",
                    "lastName" => "lastName",
                ],
                'postcode'
            ],
            [
                [
                    "firstName" => "firstName",
                    "lastName" => "lastName",
                    "address" => $this->getFakeAddress(),
                ],
                'manualAddress'
            ]
        ];
    }

    public function testVoucherDobMatchError(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData([
            "firstName" => "firstName",
            "lastName" => "lastName"
        ]);

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this
            ->siriusApiServiceMock
            ->expects($this->exactly(2))
            ->method("getLpaByUid")
            ->willReturnCallback(fn (string $lpa) => match (true) {
                $lpa === 'M-XYXY-YAGA-35G3' => ["lpaData" => "one"],
                $lpa === 'M-AAAA-1234-5678' => ["lpaData" => "two"],
            });

        $this
            ->voucherMatchMock
            ->expects($this->exactly(2))
            ->method("checkMatch")
            ->willReturnMap([
                [["lpaData" => "one"], "firstName", "lastName", '1980-01-01', false],
                [["lpaData" => "two"], "firstName", "lastName", '1980-01-01', [
                    "firstName" => "firstName",
                    "lastName" => "lastName",
                    "dob" => "1980-01-01",
                    "type" => LpaActorTypes::DONOR->value
                ]]
            ]);

        $this->dispatch("/$this->uuid/{$this->routes['dob']}", 'POST', [
            "dob_day" => "01",
            "dob_month" => "01",
            "dob_year" => "1980"
        ]);
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains(
            'div[name=donor_warning]',
            'The person vouching cannot have the same name and date of birth as the donor.'
        );
    }

    public function testVoucherDobUnderageError(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/{$this->routes['dob']}", 'POST', [
            "dob_day" => "20",
            "dob_month" => "11",
            "dob_year" => "2024"
        ]);
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains(
            'div[name=donor_underage_warning]',
            'The person vouching must be 18 years or older.'
        );
    }

    public function testVoucherDobEmptyError(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/{$this->routes['dob']}", 'POST', [
            "dob_day" => "",
            "dob_month" => "",
            "dob_year" => ""
        ]);
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains(
            'div[name=date_problem]',
            'Error:Enter their date of birth'
        );
    }

    public function testVoucherDobInvalidError(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/{$this->routes['dob']}", 'POST', [
            "dob_day" => "999",
            "dob_month" => "999",
            "dob_year" => "thing"
        ]);
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains(
            'div[name=date_problem]',
            'Error:Date of birth must be a valid date'
        );
    }

    public function testEnterPostcodePage(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/{$this->routes['postcode']}", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(VouchingFlowController::class);
        $this->assertControllerClass('VouchingFlowController');
        $this->assertMatchedRouteName('root/voucher_enter_postcode');
    }

    public function testEnterPostcodeError(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this
            ->siriusApiServiceMock
            ->expects(self::once())
            ->method('searchAddressesByPostcode')
            ->willReturn([]);

        $this->dispatch("/$this->uuid/{$this->routes['postcode']}", 'POST', [
            "postcode" => "A12 3BC"
        ]);
        $this->assertResponseStatusCode(200);
        $this->assertQuery('p[id=postcode-error]');
    }

    public function testEnterPostcodeRedirect(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this
            ->siriusApiServiceMock
            ->expects(self::once())
            ->method('searchAddressesByPostcode')
            ->willReturn(["response" => true]);

        $this->dispatch("/$this->uuid/{$this->routes['postcode']}", 'POST', [
            "postcode" => "FA2 3KE"
        ]);
        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo("/$this->uuid/{$this->routes['selectAddress']}/FA2%203KE");
    }

    public function testSelectAddressPage(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this
            ->siriusApiServiceMock
            ->expects(self::once())
            ->method('searchAddressesByPostcode')
            ->willReturn([]);

        $this->dispatch("/$this->uuid/{$this->routes['selectAddress']}/FA2%203KE", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(VouchingFlowController::class);
        $this->assertControllerClass('VouchingFlowController');
        $this->assertMatchedRouteName('root/voucher_select_address');

        //i cant figure out how to assert that the options are populated correctly..
    }

    public function testSelectAddressRedirect(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();
        $mockResponseSearchAddress = [
            [
                'addressLine1' => $this->getFakeAddress()["line1"],
                'town' => $this->getFakeAddress()["town"],
                'postcode' => $this->getFakeAddress()["postcode"],
                'country' => $this->getFakeAddress()["country"],
            ],
        ];

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this
            ->siriusApiServiceMock
            ->expects(self::once())
            ->method('searchAddressesByPostcode')
            ->willReturn($mockResponseSearchAddress);

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('addSelectedAddress')
            ->with(
                $this->uuid,
                $this->getFakeAddress()
            );

        $this->dispatch("/$this->uuid/{$this->routes['selectAddress']}/FA2%203KE", 'POST', [
            "address_json" =>
                "{\"line1\":\"456 Pretend Road\",\"town\":\"Faketown\"," .
                "\"postcode\":\"FA2 3KE\",\"country\":\"United Kingdom\"}"
        ]);
        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo("/$this->uuid/{$this->routes['manualAddress']}");
    }

    public function testEnterAddressManualPage(): void
    {
        $fakeAddress = $this->getFakeAddress();
        unset($fakeAddress["country"]);

        $mockResponseDataIdDetails = $this->returnOpgResponseData();
        $mockResponseDataIdDetails["address"] = $fakeAddress;

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this
            ->siriusApiServiceMock
            ->expects(self::once())
            ->method('getCountryList')
            ->willReturn([
                ["handle" => "GB", "label" => "United Kingdom"],
                ["handle" => "SC", "label" => "Some Country"],
            ]);

        $this->dispatch("/$this->uuid/{$this->routes['manualAddress']}", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(VouchingFlowController::class);
        $this->assertControllerClass('VouchingFlowController');
        $this->assertMatchedRouteName('root/voucher_enter_address_manual');
        //check inputs are pre-populated if address was already selected
        $this->assertQuery("input#line1[value='456 Pretend Road']");
        $this->assertQuery("option[value='United Kingdom'][selected]");
    }

    public function testEnterAddressManualMatchError(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();
        $mockResponseDataIdDetails["address"] = $this->getFakeAddress();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this
            ->siriusApiServiceMock
            ->expects($this->exactly(2))
            ->method("getLpaByUid")
            ->willReturnOnConsecutiveCalls(["lpaData" => "one"], ["lpaData" => "two"]);

        $this
            ->voucherMatchMock
            ->expects($this->exactly(2))
            ->method("checkAddressDonorMatch")
            ->willReturnOnConsecutiveCalls(false, true);

        $this->dispatch("/$this->uuid/{$this->routes['manualAddress']}", 'POST', $this->getFakeAddress());
        $this->assertResponseStatusCode(200);
        $this->assertQuery("div[name='address_warning']");
    }

    public function testEnterAddressManualError(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/{$this->routes['manualAddress']}", 'POST', [
            "line1" => "",
            "town" => "",
            "postcode" => "NOTAPOSTCODE"
        ]);
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains("p[id='line1-error']", "Error:Enter an address");
        $this->assertQueryContentContains("p[id='town-error']", "Error:Enter a town or city");
        $this->assertQueryContentContains("p[id='postcode-error']", "Error:Enter a valid postcode");
    }

    public function testEnterAddressManualRedirect(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();
        $mockResponseDataIdDetails["address"] = $this->getFakeAddress();

        $address_w_nulls = $this->getFakeAddress();
        $address_w_nulls["line2"] = '';
        $address_w_nulls["line3"] = '';

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this
            ->siriusApiServiceMock
            ->expects($this->exactly(2))
            ->method("getLpaByUid")
            ->willReturnCallback(fn (string $lpa) => match (true) {
                $lpa === 'M-XYXY-YAGA-35G3' => ["lpaData" => "one"],
                $lpa === 'M-AAAA-1234-5678' => ["lpaData" => "two"],
            });

        $this
            ->voucherMatchMock
            ->expects($this->exactly(2))
            ->method("checkAddressDonorMatch")
            ->willReturnOnConsecutiveCalls(false, false);

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('addSelectedAddress')
            ->with(
                $this->uuid,
                $address_w_nulls
            );

        $this->dispatch("/$this->uuid/{$this->routes['manualAddress']}", 'POST', $this->getFakeAddress());
        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo("/$this->uuid/{$this->routes['confirmDonors']}");
    }

    public function testConfirmDonors(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this
            ->siriusApiServiceMock
            ->expects($this->exactly(2))
            ->method("getLpaByUid")
            ->willReturnCallback(fn (string $lpa) => match (true) {
                $lpa === 'M-XYXY-YAGA-35G3' => [
                    'opg.poas.sirius' => ['donor' => ['firstname' => 'firstname', 'surname' => 'surname']],
                    'opg.poas.lpastore' => ['lpaType' => 'personal-welfare']
                ],
                $lpa === 'M-AAAA-1234-5678' => [
                    'opg.poas.sirius' => [
                        'donor' => ['firstname' => 'another', 'surname' => 'name'],
                        'caseSubtype' => 'property-and-affairs'
                    ],
                ],
            });

        $this->dispatch("/$this->uuid/{$this->routes['confirmDonors']}", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(VouchingFlowController::class);
        $this->assertControllerClass('VouchingFlowController');
        $this->assertMatchedRouteName('root/voucher_confirm_donors');

        $this->assertQueryContentContains('span[id=lpaType]', 'PW');
        $this->assertQueryContentContains('span[id=lpaId]', 'M-XYXY-YAGA-35G3');
        $this->assertQueryContentContains('span[id=lpaType]', 'PA');
        $this->assertQueryContentContains('span[id=lpaId]', 'M-AAAA-1234-5678');
    }


    /**
     * @dataProvider confirmDonorsRedirectData
     */
    public function testConfirmDonorsRedirect(array $idMethodIncludingNation, string $expectedRedirect): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();
        $mockResponseDataIdDetails['idMethodIncludingNation'] = $idMethodIncludingNation;

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/{$this->routes['confirmDonors']}", 'POST');
        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo("/$this->uuid/$expectedRedirect");
    }

    public function confirmDonorsRedirectData(): array
    {
        return [
            [
                [
                    "id_country" => "AUT",
                    "id_method" => "DRIVING_LICENCE",
                    'id_route' => 'POST_OFFICE'
                ],
                'find-post-office-branch'
            ],
        ];
    }

    public function testAddDonorPage(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

            $this->dispatch("/$this->uuid/{$this->routes['addDonor']}", 'GET');
            $this->assertResponseStatusCode(200);
            $this->assertModuleName('application');
            $this->assertControllerName(VouchingFlowController::class);
            $this->assertControllerClass('VouchingFlowController');
            $this->assertMatchedRouteName('root/voucher_add_donor');
    }

    /**
     * @dataProvider addDonorValidationErrorData
     */
    public function testAddDonorPageLpaValidation(string $lpaId, string $validationMessage): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/{$this->routes['addDonor']}", 'POST', ['lpa' => $lpaId]);
        $this->assertQueryContentContains("p[id='validationError']", $validationMessage);
    }

    public static function addDonorValidationErrorData(): array
    {
        return [
            ['', 'Enter an LPA number to continue.'],
            ['invalid lpa number', 'The LPA needs to be valid in the format M-XXXX-XXXX-XXXX']
        ];
    }

    /**
     * @dataProvider addDonorPageLpaData
     */
    public function testAddDonorPageLpa(array $formData, array $processLpasResponse, array $queryAsserts): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();
        $lpas = ['lpa1', 'lpa2'];

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this
            ->siriusApiServiceMock
            ->expects(self::once())
            ->method("getAllLinkedLpasByUid")
            ->with($formData['lpa'])
            ->willReturn($lpas);

        $this
            ->addDonorFormHelperMock
            ->expects(self::once())
            ->method('processLpas')
            ->with($lpas, $mockResponseDataIdDetails)
            ->willReturn($processLpasResponse);

        $this->dispatch("/$this->uuid/{$this->routes['addDonor']}", 'POST', $formData);
        foreach ($queryAsserts as $queryAssert) {
            $this->assertQueryContentContains(...$queryAssert);
        }
    }

    public static function addDonorPageLpaData(): array
    {
        return [
            // problem - no LPAs returned...
            [
                [
                    'lpa' => 'M-AAAA-AAAA-AAAA'
                ],
                [
                    'problem' => true,
                    'message' => 'This is the problem message'
                ],
                [
                    ["p[id=problemMessage]", 'This is the problem message']
                ]
            ],
            // lpa with error and attached warnings
            [
                [
                    'lpa' => 'M-AAAA-AAAA-AAAA',
                ],
                [
                    'error' => true,
                    'warning' => 'actor-match',
                    'message' => 'There is a warning message',
                    'additionalRows' => [
                        [
                            'type' => 'Actor name',
                            'value' => 'Joe Blogs'
                        ],
                        [
                            'type' => 'Actor date of birth',
                            'value' => '01 Jan 2001'
                        ]
                    ]
                ],
                [
                    ["div[id=warningMessage]", 'There is a warning message'],
                    ["th[id=addRowType]", 'Actor name'],
                    ["td[id=addRowValue]", 'Joe Blogs'],
                    ["th[id=addRowType]", 'Actor date of birth'],
                    ["td[id=addRowValue]", '01 Jan 2001']
                ]
            ],
            // valid LPAs - declaration not ticked
            [
                [
                    'lpa' => 'M-AAAA-AAAA-AAAA',
                    'lpas' => ['lpa1', 'lpa2'],
                ],
                [
                    'lpasCount' => 2,
                    'lpas' => [
                        ['uId' => 'lpa1', 'type' => 'PW'],
                        ['uId' => 'lpa2', 'type' => 'PA']
                    ],
                    'donorName' => 'Joe Blogs',
                    'donorDob' => '01 Feb 2000',
                    'donorAddress' => [
                        'line1' => 'line 1 of address',
                        'town' => 'some town',
                        'postcode' => 'FA3 K12'
                    ]
                ],
                [
                    ["caption[id=lpaCount]", 'Results: 2 eligible LPAs found for this donor.'],
                    ["td[id=donorName]", 'Joe Blogs'],
                    ["td[id=donorDob]", '01 Feb 2000'],
                    ["span[id=declarationError]", "Confirm declaration to continue"],
                ]
            ]
        ];
    }

    public function testAddDonorPageLpaRedirect(): void
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
            ->expects($this->exactly(2))
            ->method('updateCaseWithLpa');

        $this->dispatch("/$this->uuid/{$this->routes['addDonor']}", 'POST', [
            'lpa' => 'M-AAAA-AAAA-AAAA',
            'lpas' => ['lpa1', 'lpa2'],
            'declaration' => 'declaration_confirmed',
        ]);
        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo("/$this->uuid/{$this->routes['confirmDonors']}");
    }

    public function testRemoveLpaAction(): void
    {
        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('updateCaseWithLpa')
            ->with($this->uuid, 'M-AAAA-AAAA-AAAA', true);

        $this->dispatch("/{$this->uuid}/vouching/remove-lpa/M-AAAA-AAAA-AAAA", "GET");
        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo("/$this->uuid/{$this->routes['confirmDonors']}");
    }
}

<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\VouchingFlowController;
use Application\Helpers\AddressProcessorHelper;
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
    private VoucherMatchLpaActorHelper&MockObject $voucherMatchMock;
    private string $uuid;
    private array $routes;
    private array $fakeAddress;

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
        ];
        $this->fakeAddress = [
            'line1' => '456 Pretend Road',
            'town' => 'Faketown',
            'postcode' => 'FA2 3KE',
            'country' => 'UK',
        ];

        $this->opgApiServiceMock = $this->createMock(OpgApiServiceInterface::class);
        $this->siriusApiServiceMock = $this->createMock(SiriusApiService::class);
        $this->voucherMatchMock = $this->createMock(VoucherMatchLpaActorHelper::class);

        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(OpgApiServiceInterface::class, $this->opgApiServiceMock);
        $serviceManager->setService(SiriusApiService::class, $this->siriusApiServiceMock);
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
                [["lpaData" => "one"], "firstName", "lastName", null, []],
                [["lpaData" => "two"], "firstName", "lastName", null, []]
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
                [["lpaData" => "one"], "firstName", "lastName", null, [
                    [
                        "firstName" => "firstName",
                        "lastName" => "lastName",
                        "dob" => "dob",
                        "type" => LpaActorTypes::DONOR->value
                    ]
                ]],
                [["lpaData" => "two"], "firstName", "lastName", null, []]
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
                [["lpaData" => "one"], "firstName", "lastName", null, [
                    [
                        "firstName" => "firstName",
                        "lastName" => "lastName",
                        "dob" => "dob",
                        "type" => LpaActorTypes::DONOR->value
                    ]
                ]],
                [["lpaData" => "two"], "firstName", "lastName", null, []]
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

    public function testVoucherDobRedirect(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();
        $mockResponseDataIdDetails["firstName"] = "firstName";
        $mockResponseDataIdDetails["lastName"] = "lastName";

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
                [["lpaData" => "one"], "firstName", "lastName", "1980-1-1", []],
                [["lpaData" => "two"], "firstName", "lastName", "1980-1-1", []]
            ]);

        $this->dispatch("/$this->uuid/{$this->routes['dob']}", 'POST', [
            "dob_day" => "1",
            "dob_month" => "1",
            "dob_year" => "1980"
        ]);

        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo("/$this->uuid/{$this->routes['postcode']}");
    }

    public function testVoucherDobMatchError(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();
        $mockResponseDataIdDetails["firstName"] = "firstName";
        $mockResponseDataIdDetails["lastName"] = "lastName";

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
                [["lpaData" => "one"], "firstName", "lastName", '1980-01-01', [
                    [
                        "firstName" => "firstName",
                        "lastName" => "lastName",
                        "dob" => "1980-01-01",
                        "type" => LpaActorTypes::DONOR->value
                    ]
                ]],
                [["lpaData" => "two"], "firstName", "lastName", '1980-01-01', []]
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
                'addressLine1' => $this->fakeAddress["line1"],
                'town' => $this->fakeAddress["town"],
                'postcode' => $this->fakeAddress["postcode"],
                'country' => $this->fakeAddress["line1"],
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
                $this->fakeAddress
            );

        $this->dispatch("/$this->uuid/{$this->routes['selectAddress']}/FA2%203KE", 'POST', [
            "address_json" => "{\"line1\":\"456 Pretend Road\",\"town\":\"Faketown\",\"postcode\":\"FA2 3KE\",\"country\":\"UK\"}"
        ]);
        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo("/$this->uuid/{$this->routes['manualAddress']}");
    }

    public function testEnterAddressManualPage(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();
        $mockResponseDataIdDetails["address"] = $this->fakeAddress;

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn($mockResponseDataIdDetails);

        $this->dispatch("/$this->uuid/{$this->routes['manualAddress']}", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(VouchingFlowController::class);
        $this->assertControllerClass('VouchingFlowController');
        $this->assertMatchedRouteName('root/voucher_enter_address_manual');
        //check imputs are pre-populated if address was already selected
        $this->assertQuery("input[value='456 Pretend Road']");
    }

    public function testEnterAddressManualMatchError(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();
        $mockResponseDataIdDetails["address"] = $this->fakeAddress;

        $address = $this->fakeAddress;
        $address["line2"] = null;
        $address["line3"] = null;

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

        $this->dispatch("/$this->uuid/{$this->routes['manualAddress']}", 'POST', $this->fakeAddress);
        $this->assertResponseStatusCode(200);
        $this->assertQuery("div[name='address_warning']");
    }

    public function testEnterAddressManualRedirect(): void
    {
        $mockResponseDataIdDetails = $this->returnOpgResponseData();
        $mockResponseDataIdDetails["address"] = $this->fakeAddress;

        $address_w_nulls = $this->fakeAddress;
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
            // ->willReturn(["lpaData" => "data"]);
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

        $this->dispatch("/$this->uuid/{$this->routes['manualAddress']}", 'POST', $this->fakeAddress);
        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo("/$this->uuid/{$this->routes['manualAddress']}");
    }

}

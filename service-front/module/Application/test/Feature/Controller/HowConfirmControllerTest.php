<?php

declare(strict_types=1);

namespace ApplicationTest\Feature\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\HowConfirmController;
use Application\Enums\DocumentType;
use Application\Enums\IdRoute;
use Application\Helpers\DTO\FormProcessorResponseDto;
use Application\Helpers\FormProcessorHelper;
use Application\PostOffice\Country;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class HowConfirmControllerTest extends AbstractHttpControllerTestCase
{
    private OpgApiServiceInterface&MockObject $opgApiServiceMock;
    private FormProcessorHelper&MockObject $formProcessorMock;
    private string $uuid;

    public function setUp(): void
    {
        $this->setApplicationConfig(include __DIR__ . '/../../../../../config/application.config.php');

        $this->uuid = '49895f88-501b-4491-8381-e8aeeaef177d';

        $this->opgApiServiceMock = $this->createMock(OpgApiServiceInterface::class);
        $this->formProcessorMock = $this->createMock(FormProcessorHelper::class);

        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(OpgApiServiceInterface::class, $this->opgApiServiceMock);
        $serviceManager->setService(FormProcessorHelper::class, $this->formProcessorMock);
    }

    /**
     * @dataProvider howWillYouConfirmRenderData
     */
    public function testHowWillYouConfirmRendersCorrectRadioButtonsGivenPersonTypeAndServiceAvailability(
        string $personType,
        array $ServiceAvailability,
        array $expectedRadios
    ): void {
        $mockResponseData = ['personType' => $personType];
        $mockServiceAvailability = [
            'data' => $ServiceAvailability,
            'messages' => []
        ];

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

        $this->dispatch("/$this->uuid/how-will-you-confirm", 'GET');

        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(HowConfirmController::class);
        $this->assertControllerClass('HowConfirmController');
        $this->assertMatchedRouteName('root/how_will_you_confirm');

        // this is slightly tricky to test as some radios are just not created at all and
        // some are hidden, so need to distinguish between the 2 in testing.
        foreach (array_merge($expectedRadios['available']) as $id) {
            $this->assertQuery("#{$id}");
        }
        foreach ($expectedRadios['unavailable'] as $id) {
            $this->assertNotQuery("#{$id}");
        }
        foreach ($expectedRadios['hidden'] as $id) {
            $this->assertQuery("div[class='govuk-radios__item moj-hidden'] > #{$id}");
        }
    }

    public static function howWillYouConfirmRenderData(): array
    {
        $serviceAvailabilityAll = [
            DocumentType::Passport->value => true,
            DocumentType::DrivingLicence->value => true,
            DocumentType::NationalInsuranceNumber->value => true,
            IdRoute::POST_OFFICE->value => true
        ];
        $serviceAvailabilityNone = [
            DocumentType::Passport->value => false,
            DocumentType::DrivingLicence->value => false,
            DocumentType::NationalInsuranceNumber->value => false,
            IdRoute::POST_OFFICE->value => false
        ];

        $coreRadios = [
            DocumentType::NationalInsuranceNumber->value,
            DocumentType::Passport->value,
            DocumentType::DrivingLicence->value,
        ];
        $postOffice = [IdRoute::POST_OFFICE->value];
        $otherMethodRadios = [
            IdRoute::VOUCHING->value,
            IdRoute::COURT_OF_PROTECTION->value
        ];

        return [
            'donor all available' => [
                'donor',
                $serviceAvailabilityAll,
                [
                    'available' => array_merge($coreRadios, $postOffice, $otherMethodRadios),
                    'unavailable' => [],
                    'hidden' => [],
                ]
            ],
            'cp all available' => [
                'certificateProvider',
                $serviceAvailabilityAll,
                [
                    'available' => array_merge($coreRadios, $postOffice),
                    'unavailable' => $otherMethodRadios,
                    'hidden' => [],
                ]
            ],
            'voucher all available' => [
                'voucher',
                $serviceAvailabilityAll,
                [
                    'available' => array_merge($coreRadios, $postOffice),
                    'unavailable' => $otherMethodRadios,
                    'hidden' => [],
                ]
            ],
            'donor all unavailable' => [
                'donor',
                $serviceAvailabilityNone,
                [
                    'available' => $otherMethodRadios,
                    'unavailable' => $postOffice,
                    'hidden' => $coreRadios,
                ]
            ],
            'cp all unavailable' => [
                'certificateProvider',
                $serviceAvailabilityNone,
                [
                    'available' => [],
                    'unavailable' => array_merge($otherMethodRadios, $postOffice),
                    'hidden' => $coreRadios,
                ]
            ],
            'voucher all unavailable' => [
                'voucher',
                $serviceAvailabilityNone,
                [
                    'available' => [],
                    'unavailable' => array_merge($otherMethodRadios, $postOffice),
                    'hidden' => $coreRadios,
                ]
            ],
            'donor passport unavailable' => [
                'donor',
                array_merge($serviceAvailabilityAll, [DocumentType::Passport->value => false]),
                [
                    'available' => array_diff(
                        array_merge($coreRadios, $postOffice, $otherMethodRadios),
                        [DocumentType::Passport->value]
                    ),
                    'unavailable' => [],
                    'hidden' => [DocumentType::Passport->value],
                ]
            ],
        ];
    }

    public function testHowWillYouConfirmShowsServiceAvailabilityMessages(): void
    {
        $message = 'This is a service availability message';
        $mockServiceAvailability = [
            'data' => [
                DocumentType::Passport->value => false,
                DocumentType::DrivingLicence->value => false,
                DocumentType::NationalInsuranceNumber->value => false,
                IdRoute::POST_OFFICE->value => false
            ],
            'messages' => ['banner' => $message]
        ];

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn(['personType' => 'donor']);

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getServiceAvailability')
            ->with($this->uuid)
            ->willReturn($mockServiceAvailability);

        $this->dispatch("/$this->uuid/how-will-you-confirm", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertControllerClass('HowConfirmController');
        $this->assertQueryContentContains('div#serviceAvailabilityBanner', $message);
    }

    /**
     * @dataProvider passortDateCheckData
     */
    public function testHowWillYouConfirmChecksPassportDateWhenCheckButtonIsPosted(
        array $checkResult,
        string $expectedQuery
    ): void {
        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn(['personType' => 'donor']);

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getServiceAvailability')
            ->with($this->uuid)
            ->willReturn([
                'data' => [
                    DocumentType::Passport->value => true,
                    DocumentType::DrivingLicence->value => true,
                    DocumentType::NationalInsuranceNumber->value => true,
                    IdRoute::POST_OFFICE->value => true
                ],
                'messages' => []
            ]);

        $mockDto = $this->createMock(FormProcessorResponseDto::class);

        $this
            ->formProcessorMock
            ->expects(self::once())
            ->method('processPassportDateForm')
            ->willReturn($mockDto);

        $mockDto
            ->expects(self::once())
            ->method('getVariables')
            ->willReturn($checkResult);

        $this->dispatch("/$this->uuid/how-will-you-confirm", 'POST', ['check_button' => true]);

        $this->assertQuery($expectedQuery);
    }

    public static function passortDateCheckData(): array
    {
        return [
            [
                ['invalid_date' => true],
                'div#invalidDateMessage'
            ],
            [
                ['valid_date' => true],
                'div#validDateMessage'
            ],
        ];
    }

    /**
     * @dataProvider idMethodData
     */
    public function testHowWillYouConfirmReturnsCorrectRouteAndUpdatesIdMethod(
        string $personType,
        string $idChoice,
        array $expectedDataToSave,
        string $expectedRedirect
    ): void {
        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getDetailsData')
            ->with($this->uuid)
            ->willReturn(['personType' => $personType]);

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('getServiceAvailability')
            ->with($this->uuid)
            ->willReturn([
                'data' => [
                    DocumentType::Passport->value => true,
                    DocumentType::DrivingLicence->value => true,
                    DocumentType::NationalInsuranceNumber->value => true,
                    IdRoute::POST_OFFICE->value => true
                ],
                'messages' => []
            ]);

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('updateIdMethod')
            ->with($this->uuid, $expectedDataToSave);

        $this->dispatch("/$this->uuid/how-will-you-confirm", 'POST', ['id_method' => $idChoice]);

        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo("/$this->uuid/{$expectedRedirect}");
    }

    public static function idMethodData(): array
    {
        return [
            'donor via post-office' => [
                'donor',
                IdRoute::POST_OFFICE->value,
                ['id_route' => IdRoute::POST_OFFICE->value],
                'post-office-documents'
            ],
            'donor choosing vouching route' => [
                'donor',
                IdRoute::VOUCHING->value,
                ['id_route' => IdRoute::VOUCHING->value],
                'what-is-vouching'
            ],
            'donor with passport' => [
                'donor',
                DocumentType::Passport->value,
                [
                    'id_route' => IdRoute::TELEPHONE->value,
                    'id_country' => Country::GBR->value,
                    'doc_type' => DocumentType::Passport->value
                ],
                'donor-details-match-check'
            ],
            'certificate provider with nino' => [
                'certificateProvider',
                DocumentType::NationalInsuranceNumber->value,
                [
                    'id_route' => IdRoute::TELEPHONE->value,
                    'id_country' => Country::GBR->value,
                    'doc_type' => DocumentType::NationalInsuranceNumber->value
                ],
                'cp/name-match-check'
            ],
            'voucher with driving licence' => [
                'voucher',
                DocumentType::DrivingLicence->value,
                [
                    'id_route' => IdRoute::TELEPHONE->value,
                    'id_country' => Country::GBR->value,
                    'doc_type' => DocumentType::DrivingLicence->value
                ],
                'vouching/voucher-name'
            ],
        ];
    }
}

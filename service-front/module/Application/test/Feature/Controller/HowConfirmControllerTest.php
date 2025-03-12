<?php

declare(strict_types=1);

namespace ApplicationTest\Feature\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\HowConfirmController;
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
            'PASSPORT' => true,
            'DRIVING_LICENCE' => true,
            'NATIONAL_INSURANCE_NUMBER' => true,
            'POST_OFFICE' => true
        ];
        $serviceAvailabilityNone = [
            'PASSPORT' => false,
            'DRIVING_LICENCE' => false,
            'NATIONAL_INSURANCE_NUMBER' => false,
            'POST_OFFICE' => false
        ];

        $coreRadios = [
            'NATIONAL_INSURANCE_NUMBER',
            'PASSPORT',
            'DRIVING_LICENCE',
        ];
        $postOffice = ['POST_OFFICE'];
        $otherMethodRadios = [
            'ON_BEHALF',
            'COURT_OF_PROTECTION'
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
                array_merge($serviceAvailabilityAll, ['PASSPORT' => false]),
                [
                    'available' => array_diff(
                        array_merge($coreRadios, $postOffice, $otherMethodRadios),
                        ['PASSPORT']
                    ),
                    'unavailable' => [],
                    'hidden' => ['PASSPORT'],
                ]
            ],
        ];
    }

    public function testHowWillYouConfirmShowsServiceAvailabilityMessages(): void
    {
        $message = 'This is a serivce availability message';
        $mockServiceAvailability = [
            'data' => [
                'PASSPORT' => false,
                'DRIVING_LICENCE' => false,
                'NATIONAL_INSURANCE_NUMBER' => false,
                'POST_OFFICE' => false
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

        $this->assertQueryContentContains('span#serviceAvailabilityBanner', $message);
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
                    'PASSPORT' => true,
                    'DRIVING_LICENCE' => true,
                    'NATIONAL_INSURANCE_NUMBER' => true,
                    'POST_OFFICE' => true
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
                    'PASSPORT' => true,
                    'DRIVING_LICENCE' => true,
                    'NATIONAL_INSURANCE_NUMBER' => true,
                    'POST_OFFICE' => true
                ],
                'messages' => []
            ]);

        $this
            ->opgApiServiceMock
            ->expects(self::once())
            ->method('updateIdMethodWithCountry')
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
                'POST_OFFICE',
                ['id_route' => 'POST_OFFICE'],
                'post-office-documents'
            ],
            'donor choosing vouching route' => [
                'donor',
                'OnBehalf',
                ['id_route' => 'OnBehalf'],
                'what-is-vouching'
            ],
            'donor with passport' => [
                'donor',
                'PASSPORT',
                [
                    'id_route' => 'TELEPHONE',
                    'id_country' => Country::GBR->value,
                    'id_method' => 'PASSPORT'
                ],
                'donor-details-match-check'
            ],
            'certificate provider with nino' => [
                'certificateProvider',
                'NATIONAL_INSURANCE_NUMBER',
                [
                    'id_route' => 'TELEPHONE',
                    'id_country' => Country::GBR->value,
                    'id_method' => 'NATIONAL_INSURANCE_NUMBER'
                ],
                'cp/name-match-check'
            ],
            'voucher with driving licence' => [
                'voucher',
                'DRIVING_LICENCE',
                [
                    'id_route' => 'TELEPHONE',
                    'id_country' => Country::GBR->value,
                    'id_method' => 'DRIVING_LICENCE'
                ],
                'vouching/voucher-name'
            ],
        ];
    }
}

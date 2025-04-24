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
use Dom\Document;
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
    public function testHowWillYouConfirmRendersCorrectRadioButtonsGivenPersonTypeAndrouteAvailability(
        array $routeAvailability,
        array $expectedRadios
    ): void {
        $mockResponseData = ['personType' => 'donor'];
        $mockrouteAvailability = [
            'data' => $routeAvailability,
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
            ->method('getRouteAvailability')
            ->with($this->uuid)
            ->willReturn($mockrouteAvailability);

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
        $routeAvailabilityAll = [
            IdRoute::KBV->value => true,
            DocumentType::Passport->value => true,
            DocumentType::DrivingLicence->value => true,
            DocumentType::NationalInsuranceNumber->value => true,
            IdRoute::POST_OFFICE->value => true,
            IdRoute::VOUCHING->value => true,
            IdRoute::COURT_OF_PROTECTION->value => true
        ];
        $routeAvailabilityNone = [
            IdRoute::KBV->value => true,
            DocumentType::Passport->value => false,
            DocumentType::DrivingLicence->value => false,
            DocumentType::NationalInsuranceNumber->value => false,
            IdRoute::POST_OFFICE->value => false,
            IdRoute::VOUCHING->value => false,
            IdRoute::COURT_OF_PROTECTION->value => false
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
            'all available' => [
                $routeAvailabilityAll,
                [
                    'available' => array_merge($coreRadios, $postOffice, $otherMethodRadios),
                    'unavailable' => [],
                    'hidden' => [],
                ]
            ],
            'none available' => [
                $routeAvailabilityNone,
                [
                    'available' => [],
                    'unavailable' => array_merge($otherMethodRadios, $postOffice),
                    'hidden' => $coreRadios,
                ]
            ],
            'KBVs unavailable' => [
                array_merge(
                    $routeAvailabilityAll,
                    [
                        IdRoute::KBV->value => false,
                        DocumentType::NationalInsuranceNumber->value => false,
                        DocumentType::Passport->value => false,
                        DocumentType::DrivingLicence->value => false,
                    ]
                ),
                [
                    'available' => array_merge($postOffice, $otherMethodRadios),
                    'unavailable' => [],
                    'hidden' => $coreRadios,
                ]
            ],
            'passport unavailable' => [
                array_merge($routeAvailabilityAll, [DocumentType::Passport->value => false]),
                [
                    'available' => array_merge(
                        $postOffice,
                        $otherMethodRadios,
                        [
                            DocumentType::NationalInsuranceNumber->value,
                            DocumentType::DrivingLicence->value,
                        ]
                    ),
                    'unavailable' => [],
                    'hidden' => [DocumentType::Passport->value],
                ]
            ],
            'post office unavailable' => [
                array_merge($routeAvailabilityAll, [IdRoute::POST_OFFICE->value => false]),
                [
                    'available' => array_merge($coreRadios, $otherMethodRadios),
                    'unavailable' => $postOffice,
                    'hidden' => [],
                ]
            ],
            'vouching unavailable' => [
                array_merge($routeAvailabilityAll, [IdRoute::VOUCHING->value => false]),
                [
                    'available' => array_merge($coreRadios, $postOffice, [IdRoute::COURT_OF_PROTECTION->value]),
                    'unavailable' => [IdRoute::VOUCHING->value],
                    'hidden' => [],
                ]
            ],
        ];
    }

    public function testHowWillYouConfirmShowsrouteAvailabilityMessages(): void
    {
        $message = 'This is a service availability message';
        $mockrouteAvailability = [
            'data' => [
                DocumentType::Passport->value => false,
                DocumentType::DrivingLicence->value => false,
                DocumentType::NationalInsuranceNumber->value => false,
                IdRoute::POST_OFFICE->value => false
            ],
            'messages' => [$message]
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
            ->method('getRouteAvailability')
            ->with($this->uuid)
            ->willReturn($mockrouteAvailability);

        $this->dispatch("/$this->uuid/how-will-you-confirm", 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertControllerClass('HowConfirmController');
        $this->assertQueryContentContains('div#routeAvailabilityBanner', $message);
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
            ->method('getRouteAvailability')
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
            ->method('getRouteAvailability')
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
                ['idRoute' => IdRoute::POST_OFFICE->value],
                'post-office-documents'
            ],
            'donor choosing vouching route' => [
                'donor',
                IdRoute::VOUCHING->value,
                ['idRoute' => IdRoute::VOUCHING->value],
                'what-is-vouching'
            ],
            'donor with passport' => [
                'donor',
                DocumentType::Passport->value,
                [
                    'idRoute' => IdRoute::KBV->value,
                    'idCountry' => Country::GBR->value,
                    'docType' => DocumentType::Passport->value
                ],
                'donor-details-match-check'
            ],
            'certificate provider with nino' => [
                'certificateProvider',
                DocumentType::NationalInsuranceNumber->value,
                [
                    'idRoute' => IdRoute::KBV->value,
                    'idCountry' => Country::GBR->value,
                    'docType' => DocumentType::NationalInsuranceNumber->value
                ],
                'cp/name-match-check'
            ],
            'voucher with driving licence' => [
                'voucher',
                DocumentType::DrivingLicence->value,
                [
                    'idRoute' => IdRoute::KBV->value,
                    'idCountry' => Country::GBR->value,
                    'docType' => DocumentType::DrivingLicence->value
                ],
                'vouching/voucher-name'
            ],
        ];
    }
}

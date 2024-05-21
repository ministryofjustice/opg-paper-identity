<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Controller\IndexController;
use Application\Services\OpgApiService;
use Application\Services\SiriusApiService;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

/**
 * @psalm-import-type Lpa from SiriusApiService
 */
class IndexControllerTest extends AbstractHttpControllerTestCase
{
    public function setUp(): void
    {
        $this->setApplicationConfig(include __DIR__ . '/../../../../config/application.config.php');

        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
    }

    public function testIndexActionCanBeAccessed(): void
    {
        $this->dispatch('/', 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(IndexController::class); // as specified in router's controller name alias
        $this->assertControllerClass('IndexController');
        $this->assertMatchedRouteName('root/home');
    }

    public function testIndexActionViewModelTemplateRenderedWithinLayout(): void
    {
        $this->dispatch('/', 'GET');
        $this->assertQuery('body h1');
    }

    public function testInvalidRouteDoesNotCrash(): void
    {
        $this->dispatch('/invalid/route', 'GET');
        $this->assertResponseStatusCode(404);
    }

    /**
     * @return array<string, array{Lpa, string, array<mixed>}>
     */
    public static function startActionDataProvider(): array
    {
        $siriusData = ['donor' => [
            'firstname' => 'Lili', 'surname' => 'Laur', 'dob' => '2019-02-18',
            'addressLine1' => '17 East Lane', 'addressLine2' => 'Wickerham', 'town' => '', 'postcode' => 'W1 3EJ', 'country' => 'GB'],
        ];

        $lpaStoreData = [
            'donor' => [
                'firstNames' => 'Lilith', 'lastName' => 'Laur', 'dateOfBirth' => '2009-02-18',
                'address' => [
                    'line1' => 'Unit 15', 'line2' => 'Uberior House', 'town' => 'Edinburgh',
                    'postcode' => 'EH1 2EJ', 'country' => 'GB',
                ],
            ],
            'certificateProvider' => [
                'firstNames' => 'x', 'lastName' => 'x',
                'address' => ['line1' => '16a Avenida Lucana', 'line2' => 'Cordón', 'country' => 'ES'],
            ],
        ];

        return [
            'draft, donor' => [
                [
                    'opg.poas.sirius' => $siriusData,
                    'opg.poas.lpastore' => null
                ],
                'donor',
                [
                    'Lili', 'Laur', '2019-02-18', 'donor', ['M-1234-5678-90AB'],
                    ['17 East Lane', 'Wickerham', 'W1 3EJ', 'GB'],
                ]
            ],
            'executed, donor' => [
                [
                    'opg.poas.sirius' => $siriusData,
                    'opg.poas.lpastore' => $lpaStoreData
                ],
                'donor',
                [
                    'Lilith', 'Laur', '2009-02-18', 'donor', ['M-1234-5678-90AB'],
                    ['Unit 15', 'Uberior House', 'Edinburgh', 'EH1 2EJ', 'GB'],
                ]
            ],
            'executed, cp' => [
                [
                    'opg.poas.sirius' => $siriusData,
                    'opg.poas.lpastore' => $lpaStoreData
                ],
                'certificateProvider',
                [
                    'x', 'x', '1000-01-01', 'certificateProvider', ['M-1234-5678-90AB'],
                    ['16a Avenida Lucana', 'Cordón', 'ES'],
                ]
            ],
        ];
    }

    /**
     * @dataProvider startActionDataProvider
     * @param Lpa $lpa
     * @return void
     */
    public function testStartAction($lpa, string $type, array $createCaseArgs): void
    {
        $siriusApiService = $this->createMock(SiriusApiService::class);
        $opgApiService = $this->createMock(OpgApiService::class);

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setService(SiriusApiService::class, $siriusApiService);
        $serviceManager->setService(OpgApiService::class, $opgApiService);

        $siriusApiService->expects($this->once())
            ->method('getLpaByUid')
            ->willReturn($lpa);

        $opgApiService->expects($this->once())
            ->method('createCase')
            ->with(...$createCaseArgs)
            ->willReturn(['uuid' => 'e9a50129-aebf-4bbc-a5cb-916d42ee2e56']);

        $this->dispatch('/start?personType=' . $type . '&lpas[]=M-1234-5678-90AB', 'GET');
        $this->assertResponseStatusCode(302);
        $this->assertResponseHeaderRegex('Location', '/e9a50129-aebf-4bbc-a5cb-916d42ee2e56/');
    }

    /**
     * If the LPA has not yet been executed, we don't have any CP details and we can't do their ID check
     */
    public function testStartActionFailsForDraftCPs(): void
    {
        $siriusApiService = $this->createMock(SiriusApiService::class);
        $opgApiService = $this->createMock(OpgApiService::class);

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setService(SiriusApiService::class, $siriusApiService);
        $serviceManager->setService(OpgApiService::class, $opgApiService);

        $draftLpa = [
            'opg.poas.sirius' => [],
            'opg.poas.lpastore' => null
        ];

        $siriusApiService->expects($this->once())
            ->method('getLpaByUid')
            ->willReturn($draftLpa);

        $opgApiService->expects($this->never())
            ->method('createCase');

        $this->dispatch('/start?personType=certificateProvider&lpas[]=M-1234-5678-90AB', 'GET');
        $this->assertResponseStatusCode(400);
        $this->assertStringContainsString(
            'Cannot ID check this certificate provider as the LPA has not yet been submitted',
            $this->getResponse()->getBody()
        );
    }

    public function testStartActionFailsWithInvalidType(): void
    {
        $siriusApiService = $this->createMock(SiriusApiService::class);
        $opgApiService = $this->createMock(OpgApiService::class);

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setService(SiriusApiService::class, $siriusApiService);
        $serviceManager->setService(OpgApiService::class, $opgApiService);

        $draftLpa = [
            'opg.poas.sirius' => [],
            'opg.poas.lpastore' => null
        ];

        $siriusApiService->expects($this->once())
            ->method('getLpaByUid')
            ->willReturn($draftLpa);

        $opgApiService->expects($this->never())
            ->method('createCase');

        $this->dispatch('/start?personType=invalid&lpas[]=M-1234-5678-90AB', 'GET');
        $this->assertResponseStatusCode(400);
        $this->assertStringContainsString(
            'Person type &quot;invalid&quot; is not valid',
            $this->getResponse()->getBody()
        );
    }
}

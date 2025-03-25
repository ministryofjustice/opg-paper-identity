<?php

declare(strict_types=1);

namespace ApplicationTest\Feature\Controller;

use Application\Controller\IndexController;
use Application\Services\OpgApiService;
use Application\Services\SiriusApiService;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @psalm-import-type Lpa from SiriusApiService
 */
class IndexControllerTest extends AbstractHttpControllerTestCase
{
    public function setUp(): void
    {
        $this->setApplicationConfig(include __DIR__ . '/../../../../../config/application.config.php');

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
        $siriusData = [
            'uId' => 'M-0000-0000-0000',
            'id' => 1234,
            'donor' => [
                'firstname' => 'Lili', 'surname' => 'Laur', 'dob' => '18/02/2019',
                'addressLine1' => '17 East Lane', 'addressLine2' => 'Wickerham',
                'town' => '', 'postcode' => 'W1 3EJ', 'country' => 'GB',
            ],
            'caseSubtype' => 'property-and-affairs',
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
                'firstNames' => 'x', 'lastName' => 'x', 'dateOfBirth' => '1980-01-01',
                'address' => ['line1' => '16a Avenida Lucana', 'line2' => 'Cordón', 'country' => 'ES'],
            ],
            'lpaType' => 'personal-welfare',
            'attorneys' => [],
            'status' => 'in-progress',
        ];

        return [
            'draft, donor' => [
                [
                    'opg.poas.sirius' => $siriusData,
                    'opg.poas.lpastore' => null,
                ],
                'donor',
                [
                    'Lili', 'Laur', '2019-02-18', 'donor', ['M-1234-5678-90AB'],
                    [
                        'line1' => '17 East Lane',
                        'line2' => 'Wickerham',
                        'line3' => '',
                        'town' => '',
                        'postcode' => 'W1 3EJ',
                        'country' => 'GB',
                    ],
                ],
            ],
            'executed, donor' => [
                [
                    'opg.poas.sirius' => $siriusData,
                    'opg.poas.lpastore' => $lpaStoreData,
                ],
                'donor',
                [
                    'Lilith', 'Laur', '2009-02-18', 'donor', ['M-1234-5678-90AB'],
                    [
                        'line1' => 'Unit 15',
                        'line2' => 'Uberior House',
                        'line3' => '',
                        'town' => 'Edinburgh',
                        'postcode' => 'EH1 2EJ',
                        'country' => 'GB',
                    ],
                ],
            ],
            'executed, cp' => [
                [
                    'opg.poas.sirius' => $siriusData,
                    'opg.poas.lpastore' => $lpaStoreData,
                ],
                'certificateProvider',
                [
                    'x', 'x', '1000-01-01', 'certificateProvider', ['M-1234-5678-90AB'],
                    [
                        'line1' => '16a Avenida Lucana',
                        'line2' => 'Cordón',
                        'line3' => '',
                        'town' => '',
                        'postcode' => '',
                        'country' => 'ES',
                    ],
                ],
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
            'opg.poas.lpastore' => null,
        ];

        $siriusApiService->expects($this->once())
            ->method('getLpaByUid')
            ->willReturn($draftLpa);

        $opgApiService->expects($this->never())
            ->method('createCase');

        $this->dispatch('/start?personType=certificateProvider&lpas[]=M-1234-5678-90AB', 'GET');
        $this->assertResponseStatusCode(400);
        $this->assertStringContainsString(
            'ID check has status: draft and cannot be started',
            $this->getResponse()->getBody()
        );
    }

    public function testStartActionFailsWithInvalidPersonType(): void
    {
        $siriusApiService = $this->createMock(SiriusApiService::class);
        $opgApiService = $this->createMock(OpgApiService::class);

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setService(SiriusApiService::class, $siriusApiService);
        $serviceManager->setService(OpgApiService::class, $opgApiService);

        $draftLpa = [
            'opg.poas.sirius' => [],
            'opg.poas.lpastore' => null,
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

    public function testHealthCheckAction(): void
    {
        $this->dispatch('/health-check', 'GET');
        $this->assertResponseStatusCode(200);
    }

    public function testHealthCheckServiceAction(): void
    {
        $siriusApiService = $this->createMock(SiriusApiService::class);
        $opgApiService = $this->createMock(OpgApiService::class);

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setService(SiriusApiService::class, $siriusApiService);
        $serviceManager->setService(OpgApiService::class, $opgApiService);

        $siriusApiService->expects($this->once())
            ->method('checkAuth')
            ->willReturn(true);

        $opgApiService->expects($this->once())
            ->method('healthCheck')
            ->willReturn(true);

        $this->dispatch('/health-check/service', 'GET');
        $this->assertResponseStatusCode(200);
    }

    public function testAbandonAction(): void
    {
        $siriusApiService = $this->createMock(SiriusApiService::class);
        $opgApiService = $this->createMock(OpgApiService::class);

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setService(SiriusApiService::class, $siriusApiService);
        $serviceManager->setService(OpgApiService::class, $opgApiService);

        $lastPage = '/case-uuid/national-insurance-number';

        $this->dispatch(sprintf('/case-uuid/abandon-flow?last_page=%s', $lastPage), 'GET');
        $this->assertResponseStatusCode(200);

        $button = (new Crawler($this->getResponse()->getContent()))
            ->filterXPath('//a[contains(., "No, continue identity check")]');

        $this->assertEquals('/case-uuid/national-insurance-number', $button->attr('href'));
    }

    public function testAbandonActionSubmit(): void
    {
        $siriusApiService = $this->createMock(SiriusApiService::class);
        $opgApiService = $this->createMock(OpgApiService::class);

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setService(SiriusApiService::class, $siriusApiService);
        $serviceManager->setService(OpgApiService::class, $opgApiService);

        $lastPage = '/case-uuid/national-insurance-number';
        $caseUuid = 'case-uuid';
        $lpaUid = 'M-0000-0000-0000';
        $siriusPublicUrl = 'SIRIUS_PUBLIC_URL';

        $opgApiService->expects($this->once())
            ->method('getDetailsData')
            ->with($caseUuid)
            ->willReturn([
                'lpas' => [$lpaUid],
            ]);

        $opgApiService->expects($this->once())
            ->method('updateCaseProgress')
            ->with($caseUuid, $this->callback(fn ($data) => isset($data['abandonedFlow'])
                && $data['abandonedFlow']['last_page'] === $lastPage));

        $opgApiService->expects($this->once())
            ->method('sendIdentityCheck')
            ->with($caseUuid);

        $siriusApiService->expects($this->once())
            ->method('addNote')
            ->with(
                $this->callback(fn ($request) => $request !== null),
                $lpaUid,
                'ID Check Abandoned',
                'ID Check Incomplete',
                $this->callback(fn ($note) => str_contains($note, 'Reason: Call dropped')
                    && str_contains($note, 'Custom notes'))
            );

        $this->dispatch(sprintf('/%s/abandon-flow?last_page=%s', $caseUuid, $lastPage), 'POST', [
            'reason' => 'cd',
            'notes' => 'Custom notes',
        ]);

        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo("{$siriusPublicUrl}/lpa/frontend/lpa/{$lpaUid}");
    }
}

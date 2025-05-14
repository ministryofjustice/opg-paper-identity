<?php

declare(strict_types=1);

namespace ApplicationTest\Feature\Controller;

use Application\Aws\SsmHandler;
use Application\Controller\HealthcheckController;
use Application\Enums\DocumentType;
use Application\Enums\IdRoute;
use Application\Experian\Crosscore\FraudApi\FraudApiService;
use Application\Fixtures\DataQueryHandler;
use Application\Model\Entity\CaseData;
use ApplicationTest\TestCase;
use Laminas\Http\Headers;
use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response;
use Laminas\Stdlib\ArrayUtils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class HealthcheckControllerTest extends TestCase
{
    private DataQueryHandler&MockObject $dataQueryHandlerMock;
    private string $ssmRouteAvailability = 'service-availability';
    private LoggerInterface&MockObject $loggerMock;
    private FraudApiService $experianCrosscoreFraudApiService;
    private SsmHandler $ssmHandler;

    public function setUp(): void
    {
        // The module configuration should still be applicable for tests.
        // You can override configuration here with test case specific values,
        // such as sample view templates, path stacks, module_listener_options,
        // etc.
        $configOverrides = [];

        $this->setApplicationConfig(ArrayUtils::merge(
            include __DIR__ . '/../../../../../../config/application.config.php',
            $configOverrides
        ));

        $this->dataQueryHandlerMock = $this->createMock(DataQueryHandler::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->experianCrosscoreFraudApiService = $this->createMock(FraudApiService::class);
        $this->ssmHandler = $this->createMock(SsmHandler::class);

        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(DataQueryHandler::class, $this->dataQueryHandlerMock);
        $serviceManager->setService(LoggerInterface::class, $this->loggerMock);
        $serviceManager->setService(FraudApiService::class, $this->experianCrosscoreFraudApiService);
        $serviceManager->setService(SsmHandler::class, $this->ssmHandler);
    }

    public function dispatchJSON(string $path, string $method, mixed $data = null): void
    {
        $headers = new Headers();
        $headers->addHeaderLine('Accept', 'application/json');
        $headers->addHeaderLine('Content-Type', 'application/json');

        /** @var HttpRequest $request */
        $request = $this->getRequest();
        $request->setHeaders($headers);
        $request->setContent(is_string($data) ? $data : json_encode($data));

        $this->dispatch($path, $method);
    }

    #[DataProvider('routeAvailabilityData')]
    public function testRouteAvailability(
        string $uuid,
        CaseData $case,
        array $services,
        array $response
    ): void {
        $this->dataQueryHandlerMock
            ->expects($this->once())
            ->method('getCaseByUUID')
            ->willReturn($case);

        /**
         * @psalm-suppress UndefinedMethod
         */
        $this->ssmHandler
            ->expects($this->once())
            ->method('getJsonParameter')
            ->with($this->ssmRouteAvailability)
            ->willReturn($services);

        $this->dispatchJSON(
            sprintf('/route-availability?uuid=%s', $uuid),
            'GET'
        );

        $this->assertResponseStatusCode(Response::STATUS_CODE_200);
        $this->assertEquals($response, json_decode($this->getResponse()->getContent(), true));
        $this->assertModuleName('application');
        $this->assertControllerName(HealthcheckController::class); // as specified in router's controller name alias
        $this->assertControllerClass('HealthcheckController');
        $this->assertMatchedRouteName('route_availability');
    }

    public static function routeAvailabilityData(): array
    {
        $uuid = 'a9bc8ab8-389c-4367-8a9b-762ab3050999';

        $case = [
            "id" => "a9bc8ab8-389c-4367-8a9b-762ab3050999",
            "personType" => "donor",
            "claimedIdentity" => [
                "firstName" => "Mary Ann",
                "lastName" => "Chapman",
                "dob" => "1949-01-01",
                "address" => [
                    "postcode" => "SW1B 1BB",
                    "country" => "UK",
                    "town" => "town",
                    "line2" => "Road",
                    "line1" => "1 Street",
                ],
                "professionalAddress" => [
                ]
            ],
            "lpas" => [
                "M-XYXY-YAGA-35G3",
                "M-VGAS-OAGA-34G9",
            ],
            "documentComplete" => false,
            "idMethod" => [
                'docType' => DocumentType::DrivingLicence->value,
                'idCountry' => "GBR",
                'idRoute' => IdRoute::KBV->value,
            ],
            "caseProgress" => [
                "fraudScore" => [
                    "decision" => "ACCEPT",
                    "score" => 0
                ]
            ]
        ];

        $caseNoDec = [
            "id" => "a9bc8ab8-389c-4367-8a9b-762ab3050999",
            "personType" => "donor",
            "claimedIdentity" => [
                "firstName" => "Mary Ann",
                "lastName" => "Chapman",
                "dob" => "1949-01-01",
                "address" => [
                    "postcode" => "SW1B 1BB",
                    "country" => "UK",
                    "town" => "town",
                    "line2" => "Road",
                    "line1" => "1 Street",
                ],
                "professionalAddress" => [
                ]
            ],
            "lpas" => [
                "M-XYXY-YAGA-35G3",
                "M-VGAS-OAGA-34G9",
            ],
            "documentComplete" => false,
            "idMethod" => [
                'docType' => DocumentType::DrivingLicence->value,
                'idCountry' => "GBR",
                'idRoute' => IdRoute::KBV->value,
            ],
            "caseProgress" => [
                "fraudScore" => [
                    "decision" => "NODECISION",
                    "score" => 0
                ]
            ]
        ];

        $services = [
            IdRoute::KBV->value => true,
            DocumentType::NationalInsuranceNumber->value => true,
            DocumentType::DrivingLicence->value => true,
            DocumentType::Passport->value => true,
            IdRoute::POST_OFFICE->value => true,
        ];

        $response = [
            'data' => [
                DocumentType::Passport->value => true,
                DocumentType::DrivingLicence->value => true,
                DocumentType::NationalInsuranceNumber->value => true,
                IdRoute::POST_OFFICE->value => true,
                IdRoute::VOUCHING->value => true,
                IdRoute::COURT_OF_PROTECTION->value => true,
                IdRoute::KBV->value => true,
            ],
            'messages' => [],
        ];

        $responseNoDec = [
            'data' => [
                IdRoute::KBV->value => false,
                DocumentType::NationalInsuranceNumber->value => false,
                DocumentType::DrivingLicence->value => false,
                DocumentType::Passport->value => false,
                IdRoute::POST_OFFICE->value => true,
                IdRoute::VOUCHING->value => true,
                IdRoute::COURT_OF_PROTECTION->value => true
            ],
            'messages' => [
                'The donor cannot ID over the phone due to a lack of ' .
                'available security questions or failure to answer them correctly on a previous occasion.',
            ],
        ];

        return [
            [
                $uuid,
                CaseData::fromArray($case),
                $services,
                $response
            ],
            [
                $uuid,
                CaseData::fromArray($caseNoDec),
                $services,
                $responseNoDec
            ]
        ];
    }
}

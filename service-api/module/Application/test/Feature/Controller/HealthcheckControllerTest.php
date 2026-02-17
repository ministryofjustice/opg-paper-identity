<?php

declare(strict_types=1);

namespace ApplicationTest\Feature\Controller;

use Application\Auth\Listener;
use Application\Aws\SsmHandler;
use Application\Enums\DocumentType;
use Application\Enums\IdRoute;
use Application\Enums\PersonType;
use Application\Experian\Crosscore\FraudApi\FraudApiService;
use Application\Fixtures\DataQueryHandler;
use Application\Model\Entity\CaseData;
use Laminas\Http\Response;
use Laminas\Mvc\Application;
use Laminas\Stdlib\ArrayUtils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class HealthcheckControllerTest extends BaseControllerTestCase
{
    private DataQueryHandler&MockObject $dataQueryHandlerMock;
    private string $ssmRouteAvailability = 'service-availability';
    private LoggerInterface&MockObject $loggerMock;
    private FraudApiService&MockObject $experianCrosscoreFraudApiService;
    private SsmHandler&MockObject $ssmHandler;

    public function setUp(): void
    {
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

        // Disable authentication during tests
        $listener = $this->getApplicationServiceLocator()->get(Listener::class);
        $listener->detach($this->getApplication()->getEventManager());
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

        $this->ssmHandler
            ->expects($this->once())
            ->method('getJsonParameter')
            ->with($this->ssmRouteAvailability)
            ->willReturn($services);

        $this->dispatch(
            sprintf('/route-availability?uuid=%s', $uuid),
            'GET'
        );

        $this->assertResponseStatusCode(Response::STATUS_CODE_200);
        $this->assertEquals($response, json_decode($this->getResponse()->getContent(), true));
        $this->assertMatchedRouteName('route_availability');
    }

    public static function routeAvailabilityData(): array
    {
        $uuid = 'a9bc8ab8-389c-4367-8a9b-762ab3050999';

        $case = [
            "id" => "a9bc8ab8-389c-4367-8a9b-762ab3050999",
            "personType" => PersonType::Donor->value,
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
                ],
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
                    "score" => 0,
                ],
            ],
        ];

        $caseNoDec = [
            "id" => "a9bc8ab8-389c-4367-8a9b-762ab3050999",
            "personType" => PersonType::Donor->value,
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
                ],
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
                    "score" => 0,
                ],
            ],
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
                IdRoute::COURT_OF_PROTECTION->value => true,
            ],
            'messages' => [
                'The donor cannot ID over the phone due to a lack of ' .
                'available security questions or failure to answer them correctly.',
            ],
        ];

        return [
            [
                $uuid,
                CaseData::fromArray($case),
                $services,
                $response,
            ],
            [
                $uuid,
                CaseData::fromArray($caseNoDec),
                $services,
                $responseNoDec,
            ],
        ];
    }
}

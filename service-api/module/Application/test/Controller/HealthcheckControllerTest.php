<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Controller\HealthcheckController;
use Application\Controller\IdentityController;
use Application\Experian\Crosscore\FraudApi\DTO\ResponseDTO;
use Application\Experian\Crosscore\FraudApi\FraudApiService;
use Application\Fixtures\DataQueryHandler;
use Application\Fixtures\DataWriteHandler;
use Application\Model\Entity\CaseData;
use Application\Yoti\SessionConfig;
use Application\Yoti\YotiService;
use Application\Yoti\YotiServiceInterface;
use ApplicationTest\TestCase;
use Laminas\Http\Headers;
use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response;
use Laminas\Stdlib\ArrayUtils;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Application\Fixtures\SsmHandler;

class HealthcheckControllerTest extends TestCase
{
    private DataQueryHandler&MockObject $dataQueryHandlerMock;
    private string $ssmServiceAvailability = 'service-availability';
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
            include_once __DIR__ . '/../../../../config/application.config.php',
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

    /**
     * @dataProvider serviceAvailabilityData
     */
    public function testServiceAvailabilityAction(
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
            ->method('getParameter')
            ->with($this->ssmServiceAvailability)
            ->willReturn($services);

        $this->dispatchJSON(
            sprintf('/service-availability?uuid=%s', $uuid),
            'GET'
        );

        $this->assertResponseStatusCode(Response::STATUS_CODE_200);
        $this->assertEquals(json_encode($response), $this->getResponse()->getContent());
        $this->assertModuleName('application');
        $this->assertControllerName(HealthcheckController::class); // as specified in router's controller name alias
        $this->assertControllerClass('HealthcheckController');
        $this->assertMatchedRouteName('service_availability');
    }

    public static function serviceAvailabilityData(): array
    {
        $uuid = 'a9bc8ab8-389c-4367-8a9b-762ab3050999';

        $case = [
            "id" => "a9bc8ab8-389c-4367-8a9b-762ab3050999",
            "personType" => "donor",
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
            "lpas" => [
                "M-XYXY-YAGA-35G3",
                "M-VGAS-OAGA-34G9",
            ],
            "documentComplete" => false,
            "alternateAddress" => [
            ],
            "searchPostcode" => null,
            "idMethodIncludingNation" => [
                'id_method' => "DRIVING_LICENCE",
                'id_country' => "GBR",
                'id_route' => "TELEPHONE",
            ],
            "fraudScore" => [
                "decision" => "ACCEPT",
                "decisionText" => "Accept",
                "score" => 0
            ]
        ];

        $caseNoDec = [
            "id" => "a9bc8ab8-389c-4367-8a9b-762ab3050999",
            "personType" => "donor",
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
            "lpas" => [
                "M-XYXY-YAGA-35G3",
                "M-VGAS-OAGA-34G9",
            ],
            "documentComplete" => false,
            "alternateAddress" => [
            ],
            "searchPostcode" => null,
            "idMethodIncludingNation" => [
                'id_method' => "DRIVING_LICENCE",
                'id_country' => "GBR",
                'id_route' => "TELEPHONE",
            ],
            "fraudScore" => [
                "decision" => "NODECISION",
                "decisionText" => "No Decision",
                "score" => 0
            ]
        ];

        $services = [
            "EXPERIAN" => true,
            "NATIONAL_INSURANCE_NUMBER" => true,
            "DRIVING_LICENCE" => true,
            "PASSPORT" => true,
            "POST_OFFICE" => true
        ];

        $response = [
            "EXPERIAN" => true,
            "NATIONAL_INSURANCE_NUMBER" => true,
            "DRIVING_LICENCE" => true,
            "PASSPORT" => true,
            "POST_OFFICE" => true
        ];

        $responseNoDec = [
            "EXPERIAN" => true,
            "NATIONAL_INSURANCE_NUMBER" => false,
            "DRIVING_LICENCE" => false,
            "PASSPORT" => false,
            "POST_OFFICE" => true,
            "message" => "Fraud check failure is now restricting ID options."
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

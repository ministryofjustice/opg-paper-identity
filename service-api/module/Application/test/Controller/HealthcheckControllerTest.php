<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

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
use Aws\Ssm\SsmClient;
use Aws\Result;

class HealthcheckControllerTest extends TestCase
{
    private DataQueryHandler&MockObject $dataQueryHandlerMock;
    private string $ssmServiceAvailability = 'AWS_SSM_SERVICE_AVAILABILITY';
    private LoggerInterface&MockObject $loggerMock;

    private FraudApiService $experianCrosscoreFraudApiService;
    private Result $ssmClient;

    public function setUp(): void
    {
        // The module configuration should still be applicable for tests.
        // You can override configuration here with test case specific values,
        // such as sample view templates, path stacks, module_listener_options,
        // etc.
        $configOverrides = [];

        $this->setApplicationConfig(ArrayUtils::merge(
            include __DIR__ . '/../../../../config/application.config.php',
            $configOverrides
        ));

        $this->dataQueryHandlerMock = $this->createMock(DataQueryHandler::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->experianCrosscoreFraudApiService = $this->createMock(FraudApiService::class);
        $this->ssmClient = $this->createMock(Result::class);

        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(DataQueryHandler::class, $this->dataQueryHandlerMock);
        $serviceManager->setService(LoggerInterface::class, $this->loggerMock);
        $serviceManager->setService(FraudApiService::class, $this->experianCrosscoreFraudApiService);
        $serviceManager->setService(Result::class, $this->ssmClient);
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
        string $services,
        array $response
    ): void {
        $this->dataQueryHandlerMock
            ->expects($this->once())
            ->method('getCaseByUUID')
            ->willReturn($case);

        $this->ssmClient->expects($this->once())
            ->method('getParameter')
            ->with([
                'Name' => $this->ssmServiceAvailability
            ])->willReturn($services);

        $this->dispatchJSON(
            sprintf('/service-availability?uuid=%s', $uuid),
            'GET'
        );

        $this->assertResponseStatusCode(Response::STATUS_CODE_200);
        $this->assertEquals('{"result":"' . $response . '"}', $this->getResponse()->getContent());
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class); // as specified in router's controller name alias
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('change_case_lpa/put');
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
                'id_method' => "",
                'id_country' => "",
                'id_route' => "",
            ],
        ];

        $services = json_encode([
            'Parameter' => [
                'Value' => [
                    "EXPERIAN" => true,
                    "NATIONAL_INSURANCE_NUMBER" => true,
                    "DRIVING_LICENCE" => true,
                    "PASSPORT" => true,
                    "POST_OFFICE" => true
                ]
            ]
        ]);

        $response = [
            [
                "EXPERIAN" => true,
                "NATIONAL_INSURANCE_NUMBER" => true,
                "DRIVING_LICENCE" => true,
                "PASSPORT" => true,
                "POST_OFFICE" => true
            ]
        ];

        return [
            [
                $uuid,
                CaseData::fromArray($case),
                $services,
                $response
            ]
        ];
    }
}

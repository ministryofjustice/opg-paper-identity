<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Controller\IdentityController;
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

class IdentityControllerTest extends TestCase
{
    private DataQueryHandler&MockObject $dataQueryHandlerMock;
    private DataWriteHandler&MockObject $dataImportHandler;
    private YotiService&MockObject $yotiServiceMock;
    private SessionConfig&MockObject $sessionConfigMock;

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
        $this->dataImportHandler = $this->createMock(DataWriteHandler::class);
        $this->yotiServiceMock = $this->createMock(YotiService::class);
        $this->sessionConfigMock = $this->createMock(SessionConfig::class);


        parent::setUp();

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(DataQueryHandler::class, $this->dataQueryHandlerMock);
        $serviceManager->setService(DataWriteHandler::class, $this->dataImportHandler);
        $serviceManager->setService(YotiServiceInterface::class, $this->yotiServiceMock);
        $serviceManager->setService(SessionConfig::class, $this->sessionConfigMock);
    }

    public function testInvalidRouteDoesNotCrash(): void
    {
        $this->jsonHeaders();

        $this->dispatch('/invalid/route', 'GET');
        $this->assertResponseStatusCode(404);
    }

    public function jsonHeaders(): void
    {
        $headers = new Headers();
        $headers->addHeaderLine('Accept', 'application/json');

        /** @var HttpRequest $request */
        $request = $this->getRequest();
        $request->setHeaders($headers);
    }

    public function testDetailsWithUUID(): void
    {
        $this->dataQueryHandlerMock
            ->expects($this->once())->method('getCaseByUUID')
            ->with('2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc')
            ->willReturn(CaseData::fromArray([
                'id' => '2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc',
                'personType' => 'donor',
                'firstName' => '',
                'lastName' => '',
                'dob' => '',
                'lpas' => [],
                'address' => [],
            ]));

        $this->dispatch('/identity/details?uuid=2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc', 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class);
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('details');
    }

    public function testDetailsWithNonexistentUUID(): void
    {
        $this->dispatch('/identity/details?uuid=2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc', 'GET');
        $this->assertResponseStatusCode(404);
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class);
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('details');
    }

    public function testDetailsWithNoUUID(): void
    {
        $response = '{"title":"Missing uuid"}';
        $this->dispatch('/identity/details', 'GET');
        $this->assertResponseStatusCode(400);
        $this->assertEquals($response, $this->getResponse()->getContent());
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class);
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('details');
    }

    /**
     * @param array $case
     * @param int $status
     * @return void
     * @dataProvider caseData
     */
    public function testCreate(array $case, int $status): void
    {
        $this->dispatchJSON(
            '/identity/create',
            'POST',
            $case
        );
        $this->assertResponseStatusCode($status);
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class);
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('create_case');
    }

    public static function caseData(): array
    {
        $validData = [
            'firstName' => 'firstName',
            'lastName' => 'lastName',
            'personType' => 'donor',
            'dob' => '1980-10-10',
            'lpas' => [
                'M-XYXY-YAGA-35G3',
                'M-VGAS-OAGA-34G9',
            ],
            'address' => [
                'address 1, address 2',
            ],
        ];

        return [
            [$validData, Response::STATUS_CODE_200],
            [array_merge($validData, ['lastName' => '']), Response::STATUS_CODE_400],
            [array_merge($validData, ['dob' => '11-11-2020']), Response::STATUS_CODE_400],
            [array_replace_recursive($validData, ['lpas' => ['NAHF-AHDA-NNN']]), Response::STATUS_CODE_400],
        ];
    }

    /**
     * @dataProvider ninoData
     */
    public function testNino(string $nino, string $response, int $status): void
    {
        $this->dispatchJSON(
            '/identity/validate_nino',
            'POST',
            ['nino' => $nino]
        );
        $this->assertResponseStatusCode($status);
        $this->assertModuleName('application');
        $this->assertEquals('{"status":"' . $response . '"}', $this->getResponse()->getContent());
        $this->assertControllerName(IdentityController::class); // as specified in router's controller name alias
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('validate_nino');
    }

    public static function ninoData(): array
    {
        return [
            ['AA112233A', 'PASS', Response::STATUS_CODE_200],
            ['BB112233A', 'PASS', Response::STATUS_CODE_200],
            ['AA112233D', 'NOT_ENOUGH_DETAILS', Response::STATUS_CODE_200],
            ['AA112233C', 'NO_MATCH', Response::STATUS_CODE_200],
        ];
    }

    /**
     * @dataProvider drivingLicenceData
     */
    public function testDrivingLicence(string $drivingLicenceNo, string $response, int $status): void
    {
        $this->dispatchJSON(
            '/identity/validate_driving_licence',
            'POST',
            ['dln' => $drivingLicenceNo]
        );
        $this->assertResponseStatusCode($status);
        $this->assertEquals('{"status":"' . $response . '"}', $this->getResponse()->getContent());
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class); // as specified in router's controller name alias
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('validate_driving_licence');
    }

    public static function drivingLicenceData(): array
    {
        return [
            ['CHAPM301534MA9AY', 'PASS', Response::STATUS_CODE_200],
            ['SMITH710238HA3DY', 'PASS', Response::STATUS_CODE_200],
            ['SMITH720238HA3D8', 'NO_MATCH', Response::STATUS_CODE_200],
            ['JONES630536AB3J9', 'NOT_ENOUGH_DETAILS', Response::STATUS_CODE_200],
        ];
    }


    /**
     * @dataProvider passportData
     */
    public function testPassportNumber(int $passportNumber, string $response, int $status): void
    {
        $this->dispatchJSON(
            '/identity/validate_passport',
            'POST',
            ['passport' => $passportNumber]
        );
        $this->assertResponseStatusCode($status);
        $this->assertEquals('{"status":"' . $response . '"}', $this->getResponse()->getContent());
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class); // as specified in router's controller name alias
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('validate_passport');
    }

    public static function passportData(): array
    {
        return [
            [123456788, 'NO_MATCH', Response::STATUS_CODE_200],
            [123456789, 'NOT_ENOUGH_DETAILS', Response::STATUS_CODE_200],
            [123333456, 'PASS', Response::STATUS_CODE_200],
            [123456784, 'PASS', Response::STATUS_CODE_200],
        ];
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
     * @dataProvider lpaAddData
     */
    public function testAddLpaToCase(
        string $uuid,
        string $lpa,
        CaseData $modelResponse,
        bool $stop,
        string $response
    ): void {
        $this->dataQueryHandlerMock
            ->expects($this->once())
            ->method('getCaseByUUID')
            ->willReturn($modelResponse);

        if (! $stop) {
            $this->dataImportHandler
                ->expects($this->once())
                ->method('updateCaseData');
        }

        $this->dispatchJSON(
            '/cases/' . $uuid . '/lpas/' . $lpa,
            'PUT'
        );

        $this->assertResponseStatusCode(Response::STATUS_CODE_200);
        $this->assertEquals('{"result":"' . $response . '"}', $this->getResponse()->getContent());
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class); // as specified in router's controller name alias
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('change_case_lpa/put');
    }

    public static function lpaAddData(): array
    {
        $uuid = 'a9bc8ab8-389c-4367-8a9b-762ab3050999';
        $newLpa = 'M-0000-0000-0000';
        $duplicatedLpa = 'M-XYXY-YAGA-35G3';

        $modelResponse = [
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
            "idMethod" => null,
            "idMethodIncludingNation" => [
            ],
        ];

        return [
            [
                $uuid,
                $newLpa,
                CaseData::fromArray($modelResponse),
                false,
                "Updated",
            ],
            [
                $uuid,
                $duplicatedLpa,
                CaseData::fromArray($modelResponse),
                true,
                "LPA is already added to this case",
            ],
        ];
    }


    /**
     * @dataProvider lpaRemoveData
     */
    public function testRemoveLpaFromCase(
        string $uuid,
        string $lpa,
        CaseData $modelResponse,
        bool $stop,
        string $response
    ): void {
        $this->dataQueryHandlerMock
            ->expects($this->once())
            ->method('getCaseByUUID')
            ->willReturn($modelResponse);

        if (! $stop) {
            $this->dataImportHandler
                ->expects($this->once())
                ->method('updateCaseData');
        }

        $this->dispatchJSON(
            '/cases/' . $uuid . '/lpas/' . $lpa,
            'DELETE'
        );

        $this->assertResponseStatusCode(Response::STATUS_CODE_200);
        $this->assertEquals('{"result":"' . $response . '"}', $this->getResponse()->getContent());
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class); // as specified in router's controller name alias
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('change_case_lpa/delete');
    }

    public static function lpaRemoveData(): array
    {
        $uuid = 'a9bc8ab8-389c-4367-8a9b-762ab3050999';
        $notAddedLpa = 'M-0000-0000-0000';
        $addedLpa = 'M-XYXY-YAGA-35G3';

        $modelResponse = [
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
            "idMethod" => null,
            "idMethodIncludingNation" => [
            ],
        ];

        return [
            [
                $uuid,
                $addedLpa,
                CaseData::fromArray($modelResponse),
                false,
                "Removed",
            ],
            [
                $uuid,
                $notAddedLpa,
                CaseData::fromArray($modelResponse),
                true,
                "LPA is not added to this case",
            ],
        ];
    }

    /**
     * @dataProvider abandonFlowData
     */
    public function testAbandonFlow(
        string $uuid,
        array $data,
        array $response
    ): void {

        $this->dataImportHandler
            ->expects($this->once())
            ->method('updateCaseData')
            ->with(
                $uuid,
                "progressPage",
                "M",
                array_map(fn (mixed $v) => [
                    'S' => $v,
                ], $data),
            );

        $path = sprintf('/cases/%s/update-progress', $uuid);

        $this->dispatchJSON(
            $path,
            'PUT',
            $data
        );

        $this->assertResponseStatusCode(Response::STATUS_CODE_200);
        $this->assertEquals($response, json_decode($this->getResponse()->getContent(), true));
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class); // as specified in router's controller name alias
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('update_progress/put');
    }

    public static function abandonFlowData(): array
    {
        $uuid = 'a9bc8ab8-389c-4367-8a9b-762ab3050999';
        $data = [
            "route" => "name-match-check",
            "reason" => "ot",
            "notes" => "Caller didn't have all required documents",
        ];
        $response = json_decode('{"result":"Progress recorded at ' . $uuid . '/' . $data['route'] . '"}', true);

        return [
            [
                $uuid,
                $data,
                $response,
            ],
        ];
    }
}

<?php

declare(strict_types=1);

namespace ApplicationTest\Controller;

use Application\Controller\IdentityController;
use Application\Controller\IndexController;
use Laminas\Http\Headers;
use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response;
use Laminas\Stdlib\ArrayUtils;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class IdentityControllerTest extends AbstractHttpControllerTestCase
{
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

        parent::setUp();
    }

    public function testIndexActionCanBeAccessed(): void
    {
        $this->dispatch('/', 'GET');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('application');
        $this->assertControllerName(IndexController::class); // as specified in router's controller name alias
        $this->assertControllerClass('IndexController');
        $this->assertMatchedRouteName('home');
    }

    public function testIndexActionResponse(): void
    {
        $this->dispatch('/', 'GET');
        $this->assertEquals('{"Laminas":"Paper ID Service API"}', $this->getResponse()->getContent());
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

    /**
     * @dataProvider ninoData
     */
    public function testNino(string $nino, string $response, int $status): void
    {
        $this->dispatch('/identity/validate_nino', 'POST', ['nino' => $nino]);
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
            ['AA112233C', 'NO_MATCH', Response::STATUS_CODE_200]
        ];
    }

    /**
     * @dataProvider drivingLicenceData
     */
    public function testDrivingLicence(string $drivingLicenceNo, string $response, int $status): void
    {
        $this->dispatch('/identity/validate_driving_licence', 'POST', ['dln' => $drivingLicenceNo]);
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
            ['CHAPM301534MA9AX', 'PASS', Response::STATUS_CODE_200],
            ['SMITH710238HA3DX', 'PASS', Response::STATUS_CODE_200],
            ['SMITH720238HA3D8', 'NO_MATCH', Response::STATUS_CODE_200],
            ['JONES630536AB3J9', 'NOT_ENOUGH_DETAILS', Response::STATUS_CODE_200]
        ];
    }

    /**
     * @dataProvider passportData
     */
    public function testPassportNumber(int $passportNumber, string $response, int $status): void
    {
        $this->dispatch('/identity/validate_passport', 'POST', ['passport' => $passportNumber]);
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
}

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
    public function testNino(string $nino, int $status): void
    {
        $this->dispatch('/identity/validate_nino', 'POST', ['nino' => $nino]);
        $this->assertResponseStatusCode($status);
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class); // as specified in router's controller name alias
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('validate_nino');
    }

    public static function ninoData(): array
    {
        return [
            ['AA112233A', Response::STATUS_CODE_200],
            ['BB112233A', Response::STATUS_CODE_200],
            ['AA112233D', Response::STATUS_CODE_400],
            ['AA112233C', Response::STATUS_CODE_400]
        ];
    }

    /**
     * @dataProvider drivingLicenceData
     */
    public function testDrivingLicence(string $drivingLicenceNo, int $status): void
    {
        $this->dispatch('/identity/validate_driving_licence', 'POST', ['dln' => $drivingLicenceNo]);
        $this->assertResponseStatusCode($status);
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class); // as specified in router's controller name alias
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('validate_driving_licence');
    }

    public static function drivingLicenceData(): array
    {
        return [
            ['CHAPM301534MA9AX', Response::STATUS_CODE_200],
            ['SMITH710238HA3DX', Response::STATUS_CODE_400]
        ];
    }

    /**
     * @dataProvider passportData
     */
    public function testPassportNumber(string $passportNumber, int $status): void
    {
        $this->dispatch('/identity/validate_passport', 'POST', ['passport' => $passportNumber]);
        $this->assertResponseStatusCode($status);
        $this->assertModuleName('application');
        $this->assertControllerName(IdentityController::class); // as specified in router's controller name alias
        $this->assertControllerClass('IdentityController');
        $this->assertMatchedRouteName('validate_passport');
    }

    public static function passportData(): array
    {
        return [
            ['123456789', Response::STATUS_CODE_200],
            ['123456', Response::STATUS_CODE_400]
        ];
    }
}

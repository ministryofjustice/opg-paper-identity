<?php

declare(strict_types=1);

namespace Telemetry;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use OpenTelemetry\Aws\Ecs\DataProvider;
use OpenTelemetry\Aws\Ecs\Detector;
use OpenTelemetry\Aws\Xray\Propagator;
use OpenTelemetry\SDK\Registry;
use Telemetry\Instrumentation\Guzzle;
use Telemetry\Instrumentation\Laminas;

/**
 * @psalm-suppress UnusedClass
 */
class Tracer
{
    public static function initialise(): void
    {
        Registry::registerTextMapPropagator('xray', new Propagator());
        Registry::registerResourceDetector('aws', new Detector(new DataProvider(), new Client(), new HttpFactory()));

        Guzzle::register();
        Laminas::register();
    }
}
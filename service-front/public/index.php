<?php

declare(strict_types=1);

use Telemetry\Tracer;

chdir(dirname(__DIR__));

// Delegate static file requests back to the PHP built-in webserver
if (PHP_SAPI === 'cli-server' && isset($_SERVER['SCRIPT_FILENAME']) && $_SERVER['SCRIPT_FILENAME'] !== __FILE__) {
    return false;
}

// Composer autoloading
include 'vendor/autoload.php';

Tracer::initialise();

// Run the application!
(function () {
    /** @var \Psr\Container\ContainerInterface $container */
    $container = require 'config/container.php';

    /** @var \Mezzio\Application $app */
    $app = $container->get(\Mezzio\Application::class);

    // Execute programmatic/declarative middleware pipeline and routing
    // configuration statements
    (require 'config/pipeline.php')($app);
    (require 'config/routes.php')($app);

    $app->run();
})();

<?php

declare(strict_types=1);

use Laminas\Mvc\Application;
use Telemetry\Tracer;

/**
 * This makes our life easier when dealing with paths. Everything is relative
 * to the application root now.
 */
chdir(dirname(__DIR__));

// Decline static file requests back to the PHP built-in webserver
if (php_sapi_name() === 'cli-server') {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $path = realpath(__DIR__ . (is_string($requestPath) ? $requestPath : ''));
    if (is_string($path) && __FILE__ !== $path && is_file($path)) {
        return false;
    }
    unset($path);
}

// Composer autoloading
include __DIR__ . '/../vendor/autoload.php';

if (! class_exists(Application::class)) {
    throw new RuntimeException(
        "Unable to load application.\n"
        . "- Type `composer install` if you are developing locally.\n"
        . "- Type `docker-compose run laminas composer install` if you are using Docker.\n"
    );
}

Tracer::initialise();

$container = require __DIR__ . '/../config/container.php';
// Run the application!
/** @var Application $app */
$app = $container->get('Application');
$app->run();

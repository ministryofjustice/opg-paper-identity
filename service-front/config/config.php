<?php

use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ConfigAggregator\PhpFileProvider;

$aggregator = new ConfigAggregator([
    // Include cache configuration
    \Mezzio\Helper\ConfigProvider::class,
    \Mezzio\ConfigProvider::class,
    \Mezzio\Router\ConfigProvider::class,
    \Laminas\Diactoros\ConfigProvider::class,
    \Mezzio\Router\LaminasRouter\ConfigProvider::class,
    \Mezzio\Twig\ConfigProvider::class,
    \Laminas\Di\ConfigProvider::class,
    \Laminas\Form\ConfigProvider::class,

    // Default App module config
    Application\ConfigProvider::class,

    // Load development config if it exists
    new PhpFileProvider(__DIR__ . '/development.config.php'),
], '/tmp/cache/application.config.cache', [
    function (array $config) {
        $config['twig']['debug'] = $config['debug'] ?? false;

        return $config;
    },
]);

return $aggregator->getMergedConfig();

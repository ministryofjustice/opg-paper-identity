<?php

declare(strict_types=1);

namespace Application\Aws;

use Aws\EventBridge\EventBridgeClient;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class EventBridgeClientFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     * @param null|array<mixed> $options
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): EventBridgeClient
    {
        /** @var array{"aws": array<string, string|bool>} $config */
        $config = $container->get('Config');

        $eventBridgeClient = new EventBridgeClient($config['aws']);

        return $eventBridgeClient;
    }
}

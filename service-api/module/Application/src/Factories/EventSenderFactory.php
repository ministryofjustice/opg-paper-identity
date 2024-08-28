<?php

declare(strict_types=1);

namespace Application\Factories;

use Application\Sirius\EventSender;
use Aws\EventBridge\EventBridgeClient;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class EventSenderFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     * @param null|array<mixed> $options
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): EventSender
    {
        /** @var array{eventbridge: array{sirius_event_bus_name: string}} $config */
        $config = $container->get('Config');

        $eventBridgeClient = new EventSender(
            $container->get(EventBridgeClient::class),
            $config['eventbridge']['sirius_event_bus_name'],
        );

        return $eventBridgeClient;
    }
}

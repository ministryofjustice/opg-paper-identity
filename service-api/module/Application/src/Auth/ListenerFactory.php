<?php

declare(strict_types=1);

namespace Application\Auth;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class ListenerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     * @param null|array<mixed> $options
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): Listener
    {
        $apiJwtKey = getenv("API_JWT_KEY");

        if (! is_string($apiJwtKey) || $apiJwtKey === '') {
            throw new RuntimeException('API_JWT_KEY must be set');
        }

        return new Listener(
            $container->get(ClockInterface::class),
            $container->get(LoggerInterface::class),
            $apiJwtKey,
        );
    }
}

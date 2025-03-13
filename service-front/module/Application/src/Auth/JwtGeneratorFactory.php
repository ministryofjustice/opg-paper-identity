<?php

declare(strict_types=1);

namespace Application\Auth;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;

class JwtGeneratorFactory implements FactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null
    ) {
        $apiJwtKey = getenv("API_JWT_KEY");

        if (! is_string($apiJwtKey) || $apiJwtKey === '') {
            throw new RuntimeException('API_JWT_KEY must be set');
        }

        return new JwtGenerator(
            $container->get(ClockInterface::class),
            $apiJwtKey,
        );
    }
}

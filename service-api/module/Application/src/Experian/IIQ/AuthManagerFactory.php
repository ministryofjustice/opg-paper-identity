<?php

declare(strict_types=1);

namespace Application\Experian\IIQ;

use Laminas\Cache\Storage\Adapter\Apcu;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class AuthManagerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     * @param null|array<mixed> $options
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): AuthManager
    {
        $hourInSeconds = 60 * 60;

        $storage = new Apcu([
           'ttl' => 4 * $hourInSeconds,
        ]);

        return new AuthManager($storage, $container->get(WaspService::class));
    }
}

<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Exceptions\StartupException;
use Application\Helpers\RouteHelper;
use Laminas\Router\RouteStackInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class RouteHelperFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string                          $requestedName
     * @param array<mixed>|null               $options
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null
    ): RouteHelper {
        $siriusPublicUrl = getenv("SIRIUS_PUBLIC_URL");

        if (! is_string($siriusPublicUrl)) {
            throw new StartupException('SIRIUS_PUBLIC_URL environment variable not set');
        }

        return new RouteHelper(
            $container->get(RouteStackInterface::class),
            $siriusPublicUrl,
        );
    }
}

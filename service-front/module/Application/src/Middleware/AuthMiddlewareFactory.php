<?php

declare(strict_types=1);

namespace Application\Middleware;

use Application\Services\SiriusApiService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class AuthMiddlewareFactory implements FactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null
    ) {
        $siriusLoginUrl = getenv("SIRIUS_PUBLIC_URL");

        return new AuthMiddleware(
            $container->get(SiriusApiService::class),
            $siriusLoginUrl === false ? "" : $siriusLoginUrl,
        );
    }
}

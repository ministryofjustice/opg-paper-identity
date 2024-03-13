<?php

declare(strict_types=1);

namespace Application\Auth;

use Application\Services\SiriusApiService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ListenerFactory implements FactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ) {
        $siriusLoginUrl = getenv("SIRIUS_LOGIN_URL");

        return new Listener(
            $container->get(SiriusApiService::class),
            $siriusLoginUrl === false ? "" : $siriusLoginUrl,
        );
    }
}

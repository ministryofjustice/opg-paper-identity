<?php

declare(strict_types=1);

namespace Application\Auth;

use GuzzleHttp\Client;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ListenerFactory implements FactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ) {
        return new Listener(
            new Client([
                'base_uri' => getenv("SIRIUS_BASE_URL") ?: ""
            ]),
            getenv("SIRIUS_LOGIN_URL") ?: "",
        );
    }
}

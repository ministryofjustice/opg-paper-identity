<?php

declare(strict_types=1);

namespace Application\Aws;

use Aws\Ssm\SsmClient;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Telemetry\Middleware\Aws as MiddlewareAws;

class SsmClientFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     * @param null|array<mixed> $options
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): SsmClient
    {
        /** @var array{"aws": array<string, string|bool>} $config */
        $config = $container->get('Config');

        $ssmClient = new SsmClient($config['aws']);

        return $ssmClient;
    }
}
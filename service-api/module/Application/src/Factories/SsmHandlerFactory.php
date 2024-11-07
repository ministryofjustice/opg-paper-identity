<?php

declare(strict_types=1);

namespace Application\Factories;

use Application\Fixtures\SsmHandler;
use Aws\Ssm\SsmClient;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class SsmHandlerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     * @param null|array<mixed> $options
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): SsmHandler
    {
        /** @var array{"aws": array<string, string|bool>} $config */
        $config = $container->get('Config');

        $ssmClient = new SsmClient($config['aws']);

        return new SsmHandler($ssmClient);
    }
}

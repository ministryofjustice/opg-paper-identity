<?php

declare(strict_types=1);

namespace Application\Aws;

use Aws\Ssm\SsmClient;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class SsmHandlerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     * @param null|array<mixed> $options
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): SsmHandler
    {
        $logger = $container->get(LoggerInterface::class);

        return new SsmHandler(
            $container->get(SsmClient::class),
            $logger
        );
    }
}

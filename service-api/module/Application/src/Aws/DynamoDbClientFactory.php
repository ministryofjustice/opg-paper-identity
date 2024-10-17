<?php

declare(strict_types=1);

namespace Application\Aws;

use Aws\DynamoDb\DynamoDbClient;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class DynamoDbClientFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     * @param null|array<mixed> $options
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): DynamoDbClient
    {
        /** @var array{"aws": array<string, string|bool>} $config */
        $config = $container->get('Config');

        return new DynamoDbClient($config['aws']);
    }
}

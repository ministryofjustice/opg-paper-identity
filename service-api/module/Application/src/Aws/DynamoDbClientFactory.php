<?php

declare(strict_types=1);

namespace Application\Aws;

use Aws\DynamoDb\DynamoDbClient;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Telemetry\Middleware\Aws as MiddlewareAws;

class DynamoDbClientFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     * @param null|array<mixed> $options
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): DynamoDbClient
    {
        $config = $container->get('Config');

        $dynamoDbClient = new DynamoDbClient($config['aws']);

        //$dynamoDbClient->getHandlerList()->appendSign(MiddlewareAws::listen($dynamoDbClient), 'telemetry');

        return $dynamoDbClient;
    }
}

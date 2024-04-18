<?php

declare(strict_types=1);

namespace Application\KBV;

use Application\Fixtures\DataImportHandler;
use Application\Mock\KBV\KBVService;
use Application\KBV\KBVServiceInterface;
use Aws\DynamoDb\DynamoDbClient;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class KBVServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string                          $requestedName
     * @param array<mixed>|null               $options
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): KBVServiceInterface
    {
        /** @var bool $useMock */
        $useMock = getenv("MOCK_KBV_API");
        if ($useMock) {
            return new KBVService(new DataImportHandler(
                $container->get(DynamoDbClient::class),
                'cases', $container->get(LoggerInterface::class)
            ));
        }

        //@TODO return real KBV service when implemented
    }
}

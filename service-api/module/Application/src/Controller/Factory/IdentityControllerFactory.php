<?php

declare(strict_types=1);

namespace Ddc\Controller\Factory;

use Application\Controller\IdentityController;
use Application\Fixtures\DataImportHandler;
use Application\Fixtures\DataQueryHandler;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class IdentityControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array<mixed>|null $options
     * @return IdentityController
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): IdentityController
    {
        $dataQueryHandler = $container->get(DataQueryHandler::class);
        $dataImportHandler = $container->get(DataImportHandler::class);

        return new IdentityController($dataQueryHandler, $dataImportHandler);
    }
}

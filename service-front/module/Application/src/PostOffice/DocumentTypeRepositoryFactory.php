<?php

declare(strict_types=1);

namespace Application\PostOffice;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class DocumentTypeRepositoryFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string                          $requestedName
     * @param array<mixed>|null               $options
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): DocumentTypeRepository {
        /**
         * @var array<mixed>
         */
        $config = $container->get('config');

        return new DocumentTypeRepository($config['opg_settings']['yoti_supported_documents']['supported_countries']);
    }
}

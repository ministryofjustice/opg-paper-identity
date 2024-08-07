<?php

declare(strict_types=1);

namespace Application\Factories;

use Application\Helpers\LocalisationHelper;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class LocalisationHelperFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string                          $requestedName
     * @param array<mixed>|null               $options
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): LocalisationHelper
    {
        $config = $container->get('config');

        return new LocalisationHelper($config);
    }
}

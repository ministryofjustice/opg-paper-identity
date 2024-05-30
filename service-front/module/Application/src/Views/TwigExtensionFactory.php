<?php

declare(strict_types=1);

namespace Application\Views;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class TwigExtensionFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string                          $requestedName
     * @param array<mixed>|null               $options
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): TwigExtension
    {
        $config = $container->get('config');
        $twigDebug = $config['zend_twig']['environment']['debug'];

        return new TwigExtension($twigDebug);
    }
}

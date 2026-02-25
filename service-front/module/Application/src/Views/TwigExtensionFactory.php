<?php

declare(strict_types=1);

namespace Application\Views;

use Laminas\Http\PhpEnvironment\Request;
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
        $request = $container->get(Request::class);

        $twigDebug = $config['twig']['debug'];

        return new TwigExtension($twigDebug, $request);
    }
}

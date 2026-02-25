<?php

declare(strict_types=1);

namespace Application\Mezzio;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class ErrorResponseGeneratorFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        return new ErrorResponseGenerator(
            $container->get(TemplateRendererInterface::class),
            $container->get('config')['debug'] ?? false,
        );
    }
}

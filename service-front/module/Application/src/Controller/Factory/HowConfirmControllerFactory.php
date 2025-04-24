<?php

declare(strict_types=1);

namespace Application\Controller\Factory;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\HowConfirmController;
use Application\Helpers\FormProcessorHelper;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class HowConfirmControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param mixed[]|null $options
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): HowConfirmController
    {
        return new HowConfirmController(
            $container->get(OpgApiServiceInterface::class),
            $container->get(FormProcessorHelper::class),
        );
    }
}

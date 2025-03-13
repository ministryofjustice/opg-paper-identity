<?php

declare(strict_types=1);

namespace Application\Controller\Factory;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\DonorFlowController;
use Application\Helpers\SiriusDataProcessorHelper;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class DonorFlowControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param mixed[]|null $options
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): DonorFlowController
    {
        /** @var string $siriusPublicUrl */
        $siriusPublicUrl = getenv("SIRIUS_PUBLIC_URL");

        return new DonorFlowController(
            $container->get(OpgApiServiceInterface::class),
            $siriusPublicUrl,
            $container->get(SiriusDataProcessorHelper::class),
            $container->get(LoggerInterface::class)
        );
    }
}

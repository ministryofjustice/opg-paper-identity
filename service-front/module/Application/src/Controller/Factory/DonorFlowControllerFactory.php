<?php

declare(strict_types=1);

namespace Application\Controller\Factory;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\DonorFlowController;
use Application\Helpers\FormProcessorHelper;
use Application\Services\SiriusApiService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class DonorFlowControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param mixed[]|null $options
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): DonorFlowController
    {
        /** @var string $siriusBaseUrl */
        $siriusBaseUrl = getenv("SIRIUS_BASE_URL");
        $config = $container->get('Config');

        return new DonorFlowController(
            $container->get(OpgApiServiceInterface::class),
            $container->get(FormProcessorHelper::class),
            $container->get(SiriusApiService::class),
            $config,
            $siriusBaseUrl
        );
    }
}

<?php

declare(strict_types=1);

namespace Application\Controller\Factory;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\CPFlowController;
use Application\Helpers\FormProcessorHelper;
use Application\Helpers\AddressProcessorHelper;
use Application\Helpers\LpaFormHelper;
use Application\Helpers\SiriusDataProcessorHelper;
use Application\Services\SiriusApiService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class CPFlowControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param mixed[]|null $options
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): CPFlowController
    {
        /** @var string $siriusPublicUrl */
        $siriusPublicUrl = getenv("SIRIUS_PUBLIC_URL");
        $config = $container->get('Config');

        return new CPFlowController(
            $container->get(OpgApiServiceInterface::class),
            $container->get(FormProcessorHelper::class),
            $container->get(SiriusApiService::class),
            $container->get(AddressProcessorHelper::class),
            $container->get(LpaFormHelper::class),
            $config,
            $siriusPublicUrl,
            $container->get(SiriusDataProcessorHelper::class),
        );
    }
}

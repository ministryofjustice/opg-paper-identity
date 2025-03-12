<?php

declare(strict_types=1);

namespace Application\Controller\Factory;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\VouchingFlowController;
use Application\Helpers\AddressProcessorHelper;
use Application\Helpers\SiriusDataProcessorHelper;
use Application\Helpers\VoucherMatchLpaActorHelper;
use Application\Helpers\AddDonorFormHelper;
use Application\Helpers\FormProcessorHelper;
use Application\Services\SiriusApiService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class VouchingFlowControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param mixed[]|null $options
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): VouchingFlowController {
        /** @var string $siriusPublicUrl */
        $siriusPublicUrl = getenv("SIRIUS_PUBLIC_URL");

        return new VouchingFlowController(
            $container->get(OpgApiServiceInterface::class),
            $container->get(SiriusApiService::class),
            $container->get(FormProcessorHelper::class),
            $container->get(VoucherMatchLpaActorHelper::class),
            $container->get(AddressProcessorHelper::class),
            $container->get(AddDonorFormHelper::class),
            $container->get(SiriusDataProcessorHelper::class),
            $siriusPublicUrl,
        );
    }
}

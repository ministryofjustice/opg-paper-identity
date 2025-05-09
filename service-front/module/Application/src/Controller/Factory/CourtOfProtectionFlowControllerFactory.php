<?php

declare(strict_types=1);

namespace Application\Controller\Factory;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\CourtOfProtectionFlowController;
use Application\Helpers\SendSiriusNoteHelper;
use Application\Helpers\SiriusDataProcessorHelper;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class CourtOfProtectionFlowControllerFactory implements FactoryInterface
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
    ): CourtOfProtectionFlowController {
        /** @var string $siriusPublicUrl */
        $siriusPublicUrl = getenv("SIRIUS_PUBLIC_URL");

        return new CourtOfProtectionFlowController(
            $container->get(OpgApiServiceInterface::class),
            $container->get(SendSiriusNoteHelper::class),
            $container->get(SiriusDataProcessorHelper::class),
            $siriusPublicUrl
        );
    }
}

<?php

declare(strict_types=1);

namespace Application\Controller\Factory;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\CourtOfProtectionFlowController;
use Application\Services\SiriusApiService;
use Application\Sirius\EventSender;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Clock\ClockInterface;
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
        return new CourtOfProtectionFlowController(
            $container->get(OpgApiServiceInterface::class),
            $container->get(SiriusApiService::class),
            $container->get(ClockInterface::class),
            $container->get(EventSender::class),
        );
    }
}

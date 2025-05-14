<?php

declare(strict_types=1);

namespace Application\Controller\Factory;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\IndexController;
use Application\Exceptions\StartupException;
use Application\Helpers\LpaFormHelper;
use Application\Helpers\SendSiriusNoteHelper;
use Application\Helpers\SiriusDataProcessorHelper;
use Application\Services\SiriusApiService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class IndexControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param mixed[]|null $options
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): IndexController
    {
        $siriusPublicUrl = getenv("SIRIUS_PUBLIC_URL");

        if (! is_string($siriusPublicUrl)) {
            throw new StartupException('SIRIUS_PUBLIC_URL environment variable not set');
        }

        return new IndexController(
            $container->get(OpgApiServiceInterface::class),
            $container->get(SiriusApiService::class),
            $container->get(LpaFormHelper::class),
            $container->get(SendSiriusNoteHelper::class),
            $container->get(SiriusDataProcessorHelper::class),
            $siriusPublicUrl,
        );
    }
}

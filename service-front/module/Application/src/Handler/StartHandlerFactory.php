<?php

declare(strict_types=1);

namespace Application\Handler;

use Application\Exceptions\StartupException;
use Application\Helpers\LpaFormHelper;
use Application\Helpers\RouteHelper;
use Application\Helpers\SiriusDataProcessorHelper;
use Application\Services\SiriusApiService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class StartHandlerFactory implements FactoryInterface
{
    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): StartHandler
    {
        $siriusPublicUrl = getenv("SIRIUS_PUBLIC_URL");

        if (! is_string($siriusPublicUrl)) {
            throw new StartupException('SIRIUS_PUBLIC_URL environment variable not set');
        }

        return new StartHandler(
            $container->get(RouteHelper::class),
            $container->get(TemplateRendererInterface::class),
            $container->get(LpaFormHelper::class),
            $container->get(SiriusApiService::class),
            $container->get(SiriusDataProcessorHelper::class),
            $siriusPublicUrl,
        );
    }
}

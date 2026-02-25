<?php

declare(strict_types=1);

namespace Application\Handler\PostOffice;

use Application\Contracts\OpgApiServiceInterface;
use Application\Helpers\RouteHelper;
use Application\Helpers\SendSiriusNoteHelper;
use Application\Helpers\SiriusDataProcessorHelper;
use Application\Services\SiriusApiService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class FindPostOfficeBranchHandlerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param mixed[]|null $options
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null
    ): FindPostOfficeBranchHandler {
        /** @var array{"opg_settings": array{"identity_documents": array<string, string>}} $config */
        $config = $container->get('config');

        return new FindPostOfficeBranchHandler(
            $container->get(OpgApiServiceInterface::class),
            $container->get(RouteHelper::class),
            $container->get(SendSiriusNoteHelper::class),
            $container->get(SiriusApiService::class),
            $container->get(SiriusDataProcessorHelper::class),
            $container->get(TemplateRendererInterface::class),
            $config,
        );
    }
}

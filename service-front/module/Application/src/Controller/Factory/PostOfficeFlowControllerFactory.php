<?php

declare(strict_types=1);

namespace Application\Controller\Factory;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\PostOfficeFlowController;
use Application\Helpers\FormProcessorHelper;
use Application\Helpers\SiriusDataProcessorHelper;
use Application\PostOffice\DocumentTypeRepository;
use Application\Services\SiriusApiService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class PostOfficeFlowControllerFactory implements FactoryInterface
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
    ): PostOfficeFlowController {
        $config = $container->get('Config');

        /** @var string $siriusPublicUrl */
        $siriusPublicUrl = getenv("SIRIUS_PUBLIC_URL");

        return new PostOfficeFlowController(
            $container->get(OpgApiServiceInterface::class),
            $container->get(FormProcessorHelper::class),
            $container->get(SiriusApiService::class),
            $container->get(SiriusDataProcessorHelper::class),
            $container->get(DocumentTypeRepository::class),
            $siriusPublicUrl,
            $config,
        );
    }
}

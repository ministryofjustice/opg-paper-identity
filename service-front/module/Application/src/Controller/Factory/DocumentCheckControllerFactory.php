<?php

declare(strict_types=1);

namespace Application\Controller\Factory;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\DocumentCheckController;
use Application\Helpers\FormProcessorHelper;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class DocumentCheckControllerFactory implements FactoryInterface
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
    ): DocumentCheckController {

        $config = $container->get('Config');
        /** @var string $siriusPublicUrl */
        $siriusPublicUrl = getenv("SIRIUS_PUBLIC_URL");

        return new DocumentCheckController(
            $container->get(OpgApiServiceInterface::class),
            $container->get(FormProcessorHelper::class),
            $config,
            $siriusPublicUrl
        );
    }
}

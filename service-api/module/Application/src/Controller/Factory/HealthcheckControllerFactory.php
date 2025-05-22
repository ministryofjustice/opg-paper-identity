<?php

declare(strict_types=1);

namespace Application\Controller\Factory;

use Application\Aws\SsmHandler;
use Application\Controller\HealthcheckController;
use Application\Fixtures\DataQueryHandler;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class HealthcheckControllerFactory implements FactoryInterface
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
    ): HealthcheckController {
        /** @var string $siriusBaseUrl */
        $ssmRouteAvailability = getenv("AWS_SSM_SERVICE_AVAILABILITY");
        $config = $container->get('Config');
        /**
         * @psalm-suppress PossiblyFalseArgument
         */
        return new HealthcheckController(
            $container->get(DataQueryHandler::class),
            $container->get(SsmHandler::class),
            $ssmRouteAvailability,
            $config
        );
    }
}

<?php

declare(strict_types=1);

namespace Application\Controller\Factory;

use Application\Aws\SsmHandler;
use Application\Controller\HealthcheckController;
use Application\Fixtures\DataQueryHandler;
use Application\Services\Logging\OpgFormatter;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

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
        $logger = $container->get(LoggerInterface::class);
        /**
         * @psalm-suppress PossiblyFalseArgument
         */
        return new HealthcheckController(
            $container->get(DataQueryHandler::class),
            $container->get(SsmHandler::class),
            $ssmRouteAvailability,
            $logger,
            $config
        );
    }
}

<?php

declare(strict_types=1);

namespace Application\Controller\Factory;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\HealthcheckController;
use Application\Controller\PostOfficeFlowController;
use Application\Helpers\FormProcessorHelper;
use Application\PostOffice\DocumentTypeRepository;
use Application\Services\SiriusApiService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Application\Fixtures\SsmHandler;
use Application\Fixtures\DataQueryHandler;
use Psr\Log\LogLevel;
use Application\Services\Logging\OpgFormatter;

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
        $ssmServiceAvailability = getenv("AWS_SSM_SERVICE_AVAILABILITY");
        $config = $container->get('Config');

        $streamHandler = new StreamHandler('php://stderr', LogLevel::INFO);
        $streamHandler->setFormatter(new OpgFormatter());

        $logger = new Logger('opg-paper-identity/api', [$streamHandler]);

        /**
         * @psalm-suppress PossiblyFalseArgument
         */
        return new HealthcheckController(
            $container->get(DataQueryHandler::class),
            $container->get(SsmHandler::class),
            $ssmServiceAvailability,
            $logger,
            $config
        );
    }
}

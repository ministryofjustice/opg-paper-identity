<?php

declare(strict_types=1);

namespace Application\Controller\Factory;

use Application\Controller\KbvController;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Application\Fixtures\DataQueryHandler;
use Application\Helpers\CaseOutcomeCalculator;
use Application\KBV\KBVServiceInterface;
use Lcobucci\Clock\SystemClock;

class KbvControllerFactory implements FactoryInterface
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
    ): KbvController {
        return new KbvController(
            $container->get(DataQueryHandler::class),
            $container->get(CaseOutcomeCalculator::class),
            $container->get(KBVServiceInterface::class),
            SystemClock::fromSystemTimezone(),
        );
    }
}
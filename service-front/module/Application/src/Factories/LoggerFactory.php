<?php

declare(strict_types=1);

namespace Application\Factories;

use Application\Services\Logging\OpgFormatter;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LoggerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string                          $requestedName
     * @param array<mixed>|null               $options
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): LoggerInterface
    {
        $formatter = new OpgFormatter();

        $streamHandler = new StreamHandler('php://stderr', LogLevel::INFO);
        $streamHandler->setFormatter($formatter);

        return new Logger('opg-paper-identity/front', [$streamHandler]);
    }
}

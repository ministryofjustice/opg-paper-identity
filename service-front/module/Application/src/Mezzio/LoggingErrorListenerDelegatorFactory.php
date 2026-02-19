<?php

namespace Application\Mezzio;

use Laminas\Stratigility\Middleware\ErrorHandler;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class LoggingErrorListenerDelegatorFactory
{
    /**
     * @param callable(): ErrorHandler $factory
     * @psalm-suppress PossiblyUnusedParam - shape determined by Mezzio framework
     */
    public function __invoke(ContainerInterface $container, string $name, callable $factory): ErrorHandler
    {
        $listener = new LoggingErrorListener($container->get(LoggerInterface::class));
        $errorHandler = $factory();
        $errorHandler->attachListener($listener);

        return $errorHandler;
    }
}

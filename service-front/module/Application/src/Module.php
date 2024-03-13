<?php

declare(strict_types=1);

namespace Application;

use Laminas\Mvc\MvcEvent;
use Psr\Log\LoggerInterface;
use Throwable;

class Module
{
    public function getConfig(): array
    {
        /** @var array $config */
        $config = include __DIR__ . '/../config/module.config.php';
        return $config;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     * This is called auto-magically by the Laminas framework
     */
    public function onBootstrap(MvcEvent $event): void
    {
        $eventManager = $event->getApplication()->getEventManager();
        $eventManager->attach(MvcEvent::EVENT_FINISH, [$this, 'onFinish']);
    }

    public function onFinish(MvcEvent $event): void
    {
        // If an exception was thrown, log it
        $exception = $event->getParam('exception');
        if ($exception instanceof Throwable) {
            $serviceManager = $event->getApplication()->getServiceManager();
            $logger = $serviceManager->get(LoggerInterface::class);

            $logger->error("an unexpected error occurred", ['exception' => $exception]);
        }
    }
}

<?php

declare(strict_types=1);

namespace Application;

use Application\Exceptions\HttpException;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Psr\Log\LoggerInterface;
use Throwable;

class Module
{
    public function getConfig(): array
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     * This is called auto-magically by the Laminas framework
     */
    public function onBootstrap(MvcEvent $event): void
    {
        $eventManager = $event->getApplication()->getEventManager();
        $eventManager->attach(MvcEvent::EVENT_FINISH, [$this, 'onFinish']);
        $eventManager->attach(MvcEvent::EVENT_ROUTE, [$this, 'log']);
    }

    public function log(MvcEvent $event): void
    {
        $logger = $event->getApplication()->getServiceManager()->get(LoggerInterface::class);
        $logger->info(sprintf(
            'receiving request to %s',
            $event->getRouteMatch()?->getMatchedRouteName() ?? 'unknown route'
        ));
    }

    public function onFinish(MvcEvent $event): void
    {
        $exception = $event->getParam('exception');

        if ($exception instanceof HttpException) {
            // If an HttpException was thrown, use its status code
            /** @var Response $response */
            $response = $event->getResponse();

            $response->setStatusCode($exception->getStatusCode());
        } elseif ($exception instanceof Throwable) {
            // If any other exception was thrown, log it
            $serviceManager = $event->getApplication()->getServiceManager();
            $logger = $serviceManager->get(LoggerInterface::class);

            $logger->error("an unexpected error occurred", ['exception' => $exception]);
        }
    }
}

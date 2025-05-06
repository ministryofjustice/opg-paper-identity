<?php

declare(strict_types=1);

namespace Application;

use Application\Aws\Secrets\AwsSecret;
use Application\Aws\Secrets\AwsSecretsCache;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceManager;
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
        $eventManager->attach(MvcEvent::EVENT_FINISH, [$this, 'onFinish'], 1000000);
        $eventManager->attach(MvcEvent::EVENT_ROUTE, [$this, 'log']);

        $application = $event->getApplication();
        /** @var ServiceManager $serviceManager */
        $serviceManager = $application->getServiceManager();
        $secretsCache = $serviceManager->get(AwsSecretsCache::class);
        AwsSecret::setCache($secretsCache);
    }

    public function log(MvcEvent $event): void
    {
        $logger = $event->getApplication()->getServiceManager()->get(LoggerInterface::class);
        $logger->info(sprintf('receiving request to %s', $event->getRouteMatch()?->getMatchedRouteName() ?? 'unknown route'));
    }


    /**
     * Determines whether the error response is the default Laminas
     * error message, so that we can overwrite that
     */
    private function isGenericErrorResponse(string $body): bool
    {
        $obj = json_decode($body, true);
        if (! is_array($obj)) {
            return true;
        }

        if (! isset($obj['title'])) {
            return true;
        }

        return false;
    }

    public function onFinish(MvcEvent $event): void
    {
        /** @var Response */
        $response = $event->getResponse();

        $exception = $event->getParam('exception');

        if ($exception instanceof Throwable) {
            $serviceManager = $event->getApplication()->getServiceManager();
            $logger = $serviceManager->get(LoggerInterface::class);

            $logger->error("an unexpected error occurred", ['exception' => $exception]);
        }

        if (
            $response->getStatusCode() >= 400 &&
            (empty($response->getBody()) || $this->isGenericErrorResponse($response->getBody()))
        ) {
            $problem = [
                'status' => $response->getStatusCode(),
            ];

            if ($exception instanceof Throwable) {
                $problem['type'] = 'UnexpectedError';
                $problem['title'] = "An unexpected error occurred";
                $problem['detail'] = $exception->getMessage();
                $problem['exception'] = $exception::class;
            } else {
                $problem['type'] = 'HTTP' . $response->getStatusCode();
                $problem['title'] = $response->getReasonPhrase();
            }

            $response->getHeaders()->addHeaderLine('content-type', 'application/json');
            $response->setContent(json_encode($problem));
        }
    }
}

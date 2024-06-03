<?php

declare(strict_types=1);

namespace Application;

use Laminas\Http\Response;
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
        $eventManager->attach(MvcEvent::EVENT_FINISH, [$this, 'onFinish'], 1000000);
    }

    /**
     * Determines whether the error response is the default Laminas
     * error message, so that we can overwrite that
     */
    private function isGenericErrorResponse(string $body): bool
    {
        $obj = json_decode($body, true);
        if (!is_array($obj)) {
            return true;
        }

        if (!isset($obj['title'])) {
            return true;
        }

        return false;
    }

    public function onFinish(MvcEvent $event): void
    {
        /** @var Response */
        $response = $event->getResponse();

        if ($response->getStatusCode() >= 400 && (empty($response->getBody()) || $this->isGenericErrorResponse($response->getBody()))) {
            $exception = $event->getParam('exception');
            $problem = [
                'status' => $response->getStatusCode(),
            ];

            if ($exception instanceof Throwable) {
                $serviceManager = $event->getApplication()->getServiceManager();
                $logger = $serviceManager->get(LoggerInterface::class);

                $logger->error("an unexpected error occurred", ['exception' => $exception]);

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

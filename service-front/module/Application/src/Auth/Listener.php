<?php

declare(strict_types=1);

namespace Application\Auth;

use Application\Services\SiriusApiService;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;

class Listener extends AbstractListenerAggregate
{
    public function __construct(
        private readonly SiriusApiService $siriusApi,
        private readonly string $loginUrl,
    ) {
    }

    /**
     * @param int $priority
     */
    public function attach(EventManagerInterface $events, $priority = -200): void
    {
        if (getenv('DISABLE_AUTH_LISTENER') === "1") {
            return;
        }

        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_ROUTE,
            [$this, 'checkAuth'],
            $priority
        );
    }

    public function checkAuth(MvcEvent $e): ?Response
    {
        /** @var Response $response */
        $response = $e->getResponse();

        if (! $this->siriusApi->checkAuth($e->getRequest())) {
            $response->setContent('unauthorised, please login at ' . $this->loginUrl);
            $response->getHeaders()->addHeaderLine("Location: " . $this->loginUrl);
            $response->setStatusCode(Response::STATUS_CODE_302);
            return $response;
        }

        return null;
    }
}

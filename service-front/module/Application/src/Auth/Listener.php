<?php

declare(strict_types=1);

namespace Application\Auth;

use Application\Services\SiriusApiService;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Uri\Uri;

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

        /** @var Request $request */
        $request = $e->getRequest();

        if (! $this->siriusApi->checkAuth($request)) {
            $redirect = $this->getRedirect($request);
            $location = sprintf("Location: %s/auth?redirect=%s", $this->loginUrl, urlencode($redirect));

            $response->setContent('unauthorised, please login at ' . $this->loginUrl);
            $response->getHeaders()->addHeaderLine($location);
            $response->setStatusCode(Response::STATUS_CODE_302);
            return $response;
        }

        return null;
    }

    private function getRedirect(Request $request): string
    {
        $path = $request->getUri()->getPath();
        if ($path === null) {
            return '';
        }

        $query = $request->getUri()->getQuery();
        if ($query === null) {
            return $path;
        }

        return $path . '?' . Uri::encodeQueryFragment($query);
    }
}

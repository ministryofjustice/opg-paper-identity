<?php

declare(strict_types=1);

namespace Application\Auth;

use Application\Services\SiriusApiService;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Psr7Bridge\Psr7ServerRequest;
use Psr\Http\Message\RequestInterface;

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

        /** @var Request $request */
        $request = $e->getRequest();

        $psr7Request = Psr7ServerRequest::fromLaminas($request);
        if (! $this->siriusApi->checkAuth($psr7Request)) {
            $redirect = $this->getRedirect($psr7Request);
            $location = sprintf("Location: %s/auth?redirect=%s", $this->loginUrl, urlencode($redirect));

            $response->setContent('unauthorised, please login at ' . $this->loginUrl);
            $response->getHeaders()->addHeaderLine($location);
            $response->setStatusCode(Response::STATUS_CODE_302);

            return $response;
        }

        return null;
    }

    private function getRedirect(RequestInterface $request): string
    {
        $path = $request->getUri()->getPath();
        if ($path === '') {
            return '';
        }

        $query = $request->getUri()->getQuery();
        if ($query === '') {
            return $path;
        }

        return $path . '?' . $query;
    }
}

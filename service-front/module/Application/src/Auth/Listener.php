<?php

declare(strict_types=1);

namespace Application\Auth;

use GuzzleHttp\Client;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Throwable;

class Listener extends AbstractListenerAggregate
{
    public function __construct(
        private readonly Client $client,
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

        $cookieHeader = $e->getRequest()->getHeader('Cookie');

        try {
            $this->client->get('/api/v1/users/current', [
                'headers' => [
                    'Cookie' => $cookieHeader->getFieldValue(),
                ],
            ]);
        } catch (Throwable $e) {
            $response->setContent('unauthorised');
            $response->getHeaders()->addHeaderLine("Location: " . $this->loginUrl);
            $response->setStatusCode(Response::STATUS_CODE_302);
            return $response;
        }

        return null;
    }
}

<?php

declare(strict_types=1);

namespace Application\Middleware;

use Application\Services\SiriusApiService;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly SiriusApiService $siriusApi,
        private readonly string $loginUrl,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (getenv('DISABLE_AUTH_LISTENER') === "1") {
            return $handler->handle($request);
        }

        if (! $this->siriusApi->checkAuth($request)) {
            $redirect = $this->getRedirect($request);
            $location = sprintf("%s/auth?redirect=%s", $this->loginUrl, urlencode($redirect));

            return new RedirectResponse(
                $location,
                302,
            );
        }

        return $handler->handle($request);
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

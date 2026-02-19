<?php

declare(strict_types=1);

namespace Application\Helpers;

use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Router\RouterInterface;

class RouteHelper
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly string $siriusPublicUrl,
    ) {
    }

    public function toRedirect(string $routeName, array $params = []): RedirectResponse
    {
        return new RedirectResponse($this->router->generateUri($routeName, $params));
    }

    public function getSiriusPublicUrl(): string
    {
        return $this->siriusPublicUrl;
    }
}

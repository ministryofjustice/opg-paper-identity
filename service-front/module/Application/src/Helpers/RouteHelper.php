<?php

declare(strict_types=1);

namespace Application\Helpers;

use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Router\RouteStackInterface;

class RouteHelper
{
    public function __construct(
        private readonly RouteStackInterface $routeStack,
    ) {
    }

    public function toRedirect(string $routeName, array $params = []): RedirectResponse
    {
        return new RedirectResponse($this->routeStack->assemble($params, ['name' => $routeName]));
    }
}

<?php

declare(strict_types=1);

namespace Application\Middleware;

use Laminas\Router\RouteMatch;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AttributePromotionMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeMatch = $request->getAttribute(RouteMatch::class);
        if ($routeMatch instanceof RouteMatch) {
            foreach ($routeMatch->getParams() as $name => $value) {
                $request = $request->withAttribute($name, $value);
            }
        }

        return $handler->handle($request);
    }
}

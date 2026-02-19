<?php

declare(strict_types=1);

use Application\Middleware\AuthMiddleware as AuthListener;
use Application\Middleware\LoggerMiddleware;
use Laminas\Stratigility\Middleware\ErrorHandler;
use Mezzio\Application;
use Mezzio\Handler\NotFoundHandler;
use Mezzio\Helper\ServerUrlMiddleware;
use Mezzio\Helper\UrlHelperMiddleware;
use Mezzio\Router\Middleware\DispatchMiddleware;
use Mezzio\Router\Middleware\ImplicitHeadMiddleware;
use Mezzio\Router\Middleware\ImplicitOptionsMiddleware;
use Mezzio\Router\Middleware\MethodNotAllowedMiddleware;
use Mezzio\Router\Middleware\RouteMiddleware;

return function (Application $app,): void {
    // The error handler should be the first (most outer) middleware to catch
    // all Exceptions.
    $app->pipe(ErrorHandler::class);
    $app->pipe(LoggerMiddleware::class);
    $app->pipe(ServerUrlMiddleware::class);

    $app->pipe(RouteMiddleware::class);

    // Common routing failures
    $app->pipe(ImplicitHeadMiddleware::class);
    $app->pipe(ImplicitOptionsMiddleware::class);
    $app->pipe(MethodNotAllowedMiddleware::class);

    // Seed the UrlHelper with the routing results
    $app->pipe(UrlHelperMiddleware::class);

    // Authentication
    $app->pipe(AuthListener::class);

    // Dispatch the request
    $app->pipe(DispatchMiddleware::class);

    // Fallback
    $app->pipe(NotFoundHandler::class);
};

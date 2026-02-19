<?php

declare(strict_types=1);

namespace Application\Middleware;

use Application\Service\Logging\OpgFormatter;
use Application\Services\Logging\OpgFormatter as LoggingOpgFormatter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LoggerMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly LoggingOpgFormatter $logFormatter)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->logFormatter->setRequest($request);

        return $handler->handle($request);
    }
}

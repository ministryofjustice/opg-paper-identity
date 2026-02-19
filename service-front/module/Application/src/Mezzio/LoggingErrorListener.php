<?php

namespace Application\Mezzio;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class LoggingErrorListener
{
    public const LOG_FORMAT = '%d [%s] %s: %s';

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    /**
     * @psalm-suppress PossiblyUnusedParam - shape determined by Mezzio framework
     */
    public function __invoke(Throwable $error, ServerRequestInterface $request, ResponseInterface $response): void
    {
        $this->logger->error(sprintf(
            'Exception (%s): %s',
            $error::class,
            $error->getMessage()
        ), [
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'stackTrace' => $error->getTraceAsString(),
        ]);
    }
}

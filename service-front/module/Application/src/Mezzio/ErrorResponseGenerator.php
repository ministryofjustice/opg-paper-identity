<?php

declare(strict_types=1);

namespace Application\Mezzio;

use Application\Exceptions\HttpException;
use Laminas\Stratigility\Utils;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class ErrorResponseGenerator
{
    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly bool $isDebug = false,
    ) {
    }

    public function __invoke(
        Throwable $e,
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        if ($e instanceof HttpException) {
            $response = $response->withStatus($e->getStatusCode());
            $message = $e->getMessage();
        } else {
            $response = $response->withStatus(Utils::getStatusCode($e, $response));
            $message = $response->getReasonPhrase();
        }

        $response->getBody()->write($this->renderer->render('error/error', [
            'response' => $response,
            'request' => $request,
            'uri' => (string) $request->getUri(),
            'status' => $response->getStatusCode(),
            'reason' => $message,
            'error' => $this->isDebug ? $e : null,
        ]));

        return $response;
    }
}

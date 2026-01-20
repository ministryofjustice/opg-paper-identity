<?php

declare(strict_types=1);

namespace Application\Handler\HealthCheck;

use Application\Contracts\OpgApiServiceInterface;
use Application\Services\SiriusApiService;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Psr7Bridge\Psr7ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ServiceStatusHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly SiriusApiService $siriusApiService,
        private readonly OpgApiServiceInterface $opgApiService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $ok = true;

        $siriusResponse = $this->siriusApiService->checkAuth($request);
        if ($siriusResponse !== true) {
            $ok = false;
        }

        $apiResponse = $this->opgApiService->healthCheck();
        if ($apiResponse !== true) {
            $ok = false;
        }

        return new JsonResponse([
            'OK' => $ok,
            'dependencies' => [
                'sirius' => [
                    'ok' => $siriusResponse,
                ],
                'api' => [
                    'ok' => $apiResponse,
                ],
            ],
        ]);
    }
}

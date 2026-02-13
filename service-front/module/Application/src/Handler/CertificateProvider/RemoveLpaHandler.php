<?php

declare(strict_types=1);

namespace Application\Handler\CertificateProvider;

use Application\Contracts\OpgApiServiceInterface;
use Application\Helpers\RouteHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RemoveLpaHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly RouteHelper $routeHelper,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $uuid = $request->getAttribute('uuid');
        $lpa = $request->getAttribute('lpa');

        $this->opgApiService->updateCaseWithLpa($uuid, $lpa, true);

        return $this->routeHelper->toRedirect("root/cp_confirm_lpas", ['uuid' => $uuid]);
    }
}

<?php

declare(strict_types=1);

namespace Application\Handler\PostOffice;

use Application\Contracts\OpgApiServiceInterface;
use Application\Helpers\RouteHelper;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class WhatHappensNextHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly RouteHelper $routeHelper,
        private readonly TemplateRendererInterface $renderer,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $uuid = $request->getAttribute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $siriusUrl = $this->routeHelper->getSiriusPublicUrl() . '/lpa/frontend/lpa/' . $detailsData["lpas"][0];

        return new HtmlResponse($this->renderer->render(
            'application/pages/post_office/what_happens_next',
            [
                'details_data' => $detailsData,
                'sirius_url' => $siriusUrl,
            ]
        ));
    }
}

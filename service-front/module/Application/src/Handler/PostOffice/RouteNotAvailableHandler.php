<?php

declare(strict_types=1);

namespace Application\Handler\PostOffice;

use Application\Contracts\OpgApiServiceInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouteNotAvailableHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly TemplateRendererInterface $renderer,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $uuid = $request->getAttribute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        return new HtmlResponse($this->renderer->render(
            'application/pages/post_office/post_office_route_not_available',
            [
                'details_data' => $detailsData,
            ]
        ));
    }
}

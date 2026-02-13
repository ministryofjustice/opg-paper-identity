<?php

declare(strict_types=1);

namespace Application\Handler\Voucher;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Helpers\RouteHelper;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class IdentityCheckPassedHandler implements RequestHandlerInterface
{
    use FormBuilder;

    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly RouteHelper $routeHelper,
        private readonly TemplateRendererInterface $renderer,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $uuid = $request->getAttribute('uuid');
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $siriusEditUrl = $this->routeHelper->getSiriusPublicUrl() . '/lpa/frontend/lpa/' . $detailsData["lpas"][0];

        return new HtmlResponse($this->renderer->render(
            'application/pages/vouching/identity_check_passed',
            [
                'details_data' => $detailsData,
                'sirius_url' => $siriusEditUrl,
            ]
        ));
    }
}

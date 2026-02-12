<?php

declare(strict_types=1);

namespace Application\Handler\Donor;

use Application\Contracts\OpgApiServiceInterface;
use Application\Helpers\DateProcessorHelper;
use Application\Helpers\RouteHelper;
use Application\Helpers\SiriusDataProcessorHelper;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DonorDetailsMatchCheckHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly RouteHelper $routeHelper,
        private readonly SiriusDataProcessorHelper $siriusDataProcessorHelper,
        private readonly TemplateRendererInterface $renderer,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $uuid = $request->getAttribute('uuid');

        // Ensure we're using the latest data from Sirius, as the user may have updated it and refreshed this page
        $this->siriusDataProcessorHelper->updatePaperIdCaseFromSirius($uuid, $request);

        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $siriusEditUrl = $this->routeHelper->getSiriusPublicUrl() . '/lpa/frontend/lpa/' . $detailsData["lpas"][0];

        return new HtmlResponse($this->renderer->render(
            'application/pages/donor_details_match_check',
            [
                'details_data' => $detailsData,
                'formattedDob' => DateProcessorHelper::formatDate($detailsData['dob']),
                'uuid' => $uuid,
                'next_page' => './donor-lpa-check',
                'sirius_edit_url' => $siriusEditUrl
            ]
        ));
    }
}

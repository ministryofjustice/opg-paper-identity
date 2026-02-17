<?php

declare(strict_types=1);

namespace Application\Handler\CertificateProvider;

use Application\Contracts\OpgApiServiceInterface;
use Application\Helpers\SiriusDataProcessorHelper;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ConfirmLpasHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly SiriusDataProcessorHelper $siriusDataProcessorHelper,
        private readonly TemplateRendererInterface $renderer,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $uuid = $request->getAttribute('uuid');
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $lpaDetails = $this->siriusDataProcessorHelper->createLpaDetailsArray($detailsData, $request);

        return new HtmlResponse($this->renderer->render(
            'application/pages/cp/confirm_lpas',
            [
                'details_data' => $detailsData,
                'lpa_details' => $lpaDetails,
                'lpa_count' => count($detailsData['lpas']),
            ]
        ));
    }
}

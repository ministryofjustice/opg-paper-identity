<?php

declare(strict_types=1);

namespace Application\Handler\Donor;

use Application\Contracts\OpgApiServiceInterface;
use Application\Enums\DocumentType;
use Application\Enums\IdRoute;
use Application\Helpers\RouteHelper;
use Application\Helpers\SiriusDataProcessorHelper;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class LpaCheckHandler implements RequestHandlerInterface
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
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        if ($request->getMethod() === 'POST') {
            if (! isset($detailsData['idMethod']['idRoute'])) {
                throw new RuntimeException('ID method is not set for this case');
            }

            if ($detailsData['idMethod']['idRoute'] === IdRoute::POST_OFFICE->value) {
                return $this->routeHelper->toRedirect("root/find_post_office_branch", ['uuid' => $uuid]);
            } else {
                switch ($detailsData['idMethod']['docType'] ?? null) {
                    case DocumentType::Passport->value:
                        return $this->routeHelper->toRedirect("root/passport_number", ['uuid' => $uuid]);

                    case DocumentType::DrivingLicence->value:
                        return $this->routeHelper->toRedirect("root/driving_licence_number", ['uuid' => $uuid]);

                    case DocumentType::NationalInsuranceNumber->value:
                        return $this->routeHelper->toRedirect("root/national_insurance_number", ['uuid' => $uuid]);

                    default:
                        throw new RuntimeException('Document type is not set for this case');
                }
            }
        }

        return new HtmlResponse($this->renderer->render(
            'application/pages/donor_lpa_check',
            [
                'details_data' => $detailsData,
                'lpas' => $detailsData['lpas'],
                'lpa_count' => count($detailsData['lpas']),
                'lpa_details' => $this->siriusDataProcessorHelper->createLpaDetailsArray($detailsData, $request),
            ]
        ));
    }
}

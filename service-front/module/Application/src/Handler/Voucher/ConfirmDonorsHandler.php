<?php

declare(strict_types=1);

namespace Application\Handler\Voucher;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
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

/**
 * @psalm-import-type CaseData from OpgApiServiceInterface
 */
class ConfirmDonorsHandler implements RequestHandlerInterface
{
    use FormBuilder;

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
            $nextRoute = $this->getNextRoute($detailsData);

            return $this->routeHelper->toRedirect($nextRoute, ['uuid' => $uuid]);
        }

        return new HtmlResponse($this->renderer->render(
            'application/pages/vouching/confirm_donors',
            [
                'details_data' => $detailsData,
                'lpa_count' => count($detailsData['lpas']),
                'lpa_details' => $this->siriusDataProcessorHelper->createLpaDetailsArray($detailsData, $request),
            ]
        ));
    }

    /**
     * @param CaseData $detailsData
     */
    public function getNextRoute(array $detailsData): string
    {
        if (
            isset($detailsData['idMethod']['idRoute'])
            && $detailsData['idMethod']['idRoute'] === IdRoute::POST_OFFICE->value
        ) {
            return 'root/find_post_office_branch';
        }

        return match ($detailsData['idMethod']['docType'] ?? null) {
            DocumentType::DrivingLicence->value => 'root/driving_licence_number',
            DocumentType::NationalInsuranceNumber->value => 'root/national_insurance_number',
            DocumentType::Passport->value => 'root/passport_number',
            default => throw new RuntimeException('Unknown document type: ' .
                ($detailsData['idMethod']['docType'] ?? 'unknown')),
        };
    }
}

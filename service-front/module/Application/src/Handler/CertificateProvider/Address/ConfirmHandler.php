<?php

declare(strict_types=1);

namespace Application\Handler\CertificateProvider\Address;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Enums\DocumentType;
use Application\Enums\IdRoute;
use Application\Forms\ConfirmAddress;
use Application\Helpers\RouteHelper;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

/**
 * @psalm-import-type CaseData from OpgApiServiceInterface
 */
class ConfirmHandler implements RequestHandlerInterface
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

        $formData = (array)($request->getParsedBody());
        $form = $this->createForm(ConfirmAddress::class, $formData);

        $nextRoute = $this->getNextRoute($detailsData);

        if ($request->getMethod() === 'POST') {
            if ($formData['confirm_alt'] === 'confirmed') {
                return $this->routeHelper->toRedirect($nextRoute, ['uuid' => $uuid]);
            }

            if ($form->isValid()) {
                if ($form->get('chosenAddress')->getValue() === 'yes') {
                    return $this->routeHelper->toRedirect($nextRoute, ['uuid' => $uuid]);
                } else {
                    return $this->routeHelper->toRedirect('root/cp_enter_postcode', ['uuid' => $uuid]);
                }
            }
        }

        return new HtmlResponse($this->renderer->render(
            'application/pages/cp/confirm_address_match',
            [
                'details_data' => $detailsData,
                'form' => $form,
            ]
        ));
    }

    /**
     * @param CaseData $detailsData
     */
    public function getNextRoute(array $detailsData): string
    {
        if (isset($detailsData['idMethod']['idRoute']) && $detailsData['idMethod']['idRoute'] !== IdRoute::KBV->value) {
            return 'root/find_post_office_branch';
        }

        return match ($detailsData['idMethod']['docType'] ?? null) {
            DocumentType::NationalInsuranceNumber->value => 'root/national_insurance_number',
            DocumentType::DrivingLicence->value => 'root/driving_licence_number',
            DocumentType::Passport->value => 'root/passport_number',
            default => throw new RuntimeException('Unknown document type: ' .
                ($detailsData['idMethod']['docType'] ?? 'unknown')),
        };
    }
}

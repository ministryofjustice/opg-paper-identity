<?php

declare(strict_types=1);

namespace Application\Handler\DocumentCheck;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Enums\DocumentType;
use Application\Forms\NationalInsuranceNumber;
use Application\Helpers\DateProcessorHelper;
use Application\Helpers\FormProcessorHelper;
use Application\Helpers\RouteHelper;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class NationalInsuranceNumberHandler implements RequestHandlerInterface
{
    use FormBuilder;

    public function __construct(
        private readonly FormProcessorHelper $formProcessorHelper,
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly RouteHelper $routeHelper,
        private readonly TemplateRendererInterface $renderer,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $uuid = $request->getAttribute("uuid");
        $routeAvailability = $this->opgApiService->getRouteAvailability($uuid);

        $templates = [
            'default' => 'application/pages/national_insurance_number',
            'success' => 'application/pages/document_success',
            'fail' => 'application/pages/national_insurance_number_fail',
            'amb_fail' => 'application/pages/national_insurance_number_ambiguous_fail',
            'thin_file' => 'application/pages/thin_file_failure',
            'fraud' => 'application/pages/fraud_failure',
        ];
        $template = $templates['default'];

        $formData = (array)($request->getParsedBody());
        $form = $this->createForm(NationalInsuranceNumber::class, $formData);
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $variables = [
            'uuid' => $uuid,
            'route_availability' => $routeAvailability,
            'details_data' => $detailsData,
        ];

        if (isset($detailsData['identityCheckPassed'])) {
            $siriusUrl = $this->routeHelper->getSiriusPublicUrl() . '/lpa/frontend/lpa/' . $detailsData['lpas'][0];

            return new HtmlResponse(
                $this->renderer->render(
                    "application/pages/cannot_start",
                    [
                            ...$variables,
                            'message' => 'The identity check has already been completed',
                            'sirius_url' => $siriusUrl,
                    ]
                )
            );
        }

        $variables['formattedDob'] = DateProcessorHelper::formatDate($detailsData['dob']);
        $variables['form'] = $form;

        if ($request->getMethod() === 'POST' && $form->isValid()) {
            $formProcessorResponseDto = $this->formProcessorHelper->processNationalInsuranceNumberForm(
                $uuid,
                $form,
                $templates
            );
            $variables = array_merge($variables, $formProcessorResponseDto->getVariables());

            if ($variables['validity'] === 'PASS') {
                $fraudCheck = $this->opgApiService->requestFraudCheck($uuid);
                $template = $this->formProcessorHelper->processTemplate($fraudCheck, $templates);
                $this->opgApiService->updateCaseSetDocumentComplete(
                    $uuid,
                    DocumentType::NationalInsuranceNumber->value
                );
            } elseif ($variables['validity'] === 'MULTIPLE_MATCH') {
                $template = $templates['amb_fail'];
            } else {
                $template = $templates['fail'];
                $this->opgApiService->updateCaseSetDocumentComplete(
                    $uuid,
                    DocumentType::NationalInsuranceNumber->value,
                    false
                );
            }
        }

        return new HtmlResponse(
            $this->renderer->render(
                $template,
                $variables,
            )
        );
    }
}

<?php

declare(strict_types=1);

namespace Application\Handler\DocumentCheck;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Enums\DocumentType;
use Application\Forms\PassportDate;
use Application\Forms\PassportNumber;
use Application\Helpers\DateProcessorHelper;
use Application\Helpers\FormProcessorHelper;
use Application\Helpers\RouteHelper;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Stdlib\Parameters;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PassportNumberHandler implements RequestHandlerInterface
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
            'default' => 'application/pages/passport_number',
            'success' => 'application/pages/document_success',
            'fail' => 'application/pages/passport_number_fail',
            'thin_file' => 'application/pages/thin_file_failure',
            'fraud' => 'application/pages/fraud_failure',
        ];
        $template = $templates['default'];

        $formData = (array)($request->getParsedBody());
        $form = $this->createForm(PassportNumber::class, $formData);
        $dateSubForm = $this->createForm(PassportDate::class, $formData);
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
        $variables['date_sub_form'] = $dateSubForm;
        $variables['details_open'] = false;

        if ($request->getMethod() === 'POST') {
            if (array_key_exists('check_button', $formData)) {
                $formProcessorResponseDto = $this->formProcessorHelper->processPassportDateForm(
                    $uuid,
                    new Parameters($formData),
                    $dateSubForm,
                    $templates
                );
            } else {
                if ($form->isValid()) {
                    $variables['passport'] = $formData['passport'];
                    $formProcessorResponseDto = $this->formProcessorHelper->processPassportForm(
                        $uuid,
                        $form,
                        $templates
                    );
                    $variables['passport_indate'] = array_key_exists('inDate', $formData)
                        ? ucwords($formData['inDate']) : 'no';

                    if ($formProcessorResponseDto->getVariables()['validity'] === true) {
                        $fraudCheck = $this->opgApiService->requestFraudCheck($uuid);
                        $template = $this->formProcessorHelper->processTemplate($fraudCheck, $templates);
                        $this->opgApiService->updateCaseSetDocumentComplete($uuid, DocumentType::Passport->value);
                    } else {
                        $this->opgApiService->updateCaseSetDocumentComplete(
                            $uuid,
                            DocumentType::Passport->value,
                            false
                        );
                        $template = $templates['fail'];
                    }
                }
            }

            if (isset($formProcessorResponseDto)) {
                $variables = array_merge($variables, $formProcessorResponseDto->getVariables());
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

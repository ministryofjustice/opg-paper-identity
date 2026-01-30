<?php

declare(strict_types=1);

namespace Application\Handler\PostOffice;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Enums\PersonType;
use Application\Forms\IdMethod;
use Application\Forms\PassportDate;
use Application\Helpers\FormProcessorHelper;
use Application\Helpers\RouteHelper;
use Application\PostOffice\Country as PostOfficeCountry;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Stdlib\Parameters;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ChooseUKDocumentHandler implements RequestHandlerInterface
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
        $templates = ['default' => 'application/pages/post_office/post_office_documents'];

        $uuid = $request->getAttribute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $formData = (array)($request->getParsedBody());
        $dateSubForm = $this->createForm(PassportDate::class, $formData);
        $form = $this->createForm(IdMethod::class, $formData);

        $variables = [
            'details_data' => $detailsData,
            'form' => $form,
            'date_sub_form' => $dateSubForm,
        ];

        if ($request->getMethod() === 'POST') {
            if (array_key_exists('check_button', $formData)) {
                $formProcessorResponseDto = $this->formProcessorHelper->processPassportDateForm(
                    $uuid,
                    new Parameters($formData),
                    $dateSubForm,
                    $templates
                );

                $variables = array_merge($variables, $formProcessorResponseDto->getVariables());
            } else {
                if ($form->isValid()) {
                    $formData = $this->formToArray($form);

                    if ($formData['id_method'] == 'NONUKID') {
                        $redirect = "root/po_choose_country";
                    } else {
                        $this->opgApiService->updateIdMethod($uuid, [
                            'docType' => $formData['id_method'],
                            'idCountry' => PostOfficeCountry::GBR->value,
                        ]);
                        switch ($detailsData["personType"]) {
                            case PersonType::Voucher:
                                $redirect = "root/voucher_name";

                                break;
                            case PersonType::CertificateProvider:
                                $redirect = "root/cp_name_match_check";

                                break;
                            default:
                                $redirect = "root/donor_details_match_check";

                                break;
                        }
                    }

                    return $this->routeHelper->toRedirect($redirect, ['uuid' => $uuid]);
                }
            }
        }

        return new HtmlResponse($this->renderer->render(
            $templates['default'],
            $variables
        ));
    }
}

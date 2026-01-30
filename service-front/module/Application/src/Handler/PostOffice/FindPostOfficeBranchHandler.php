<?php

declare(strict_types=1);

namespace Application\Handler\PostOffice;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Enums\DocumentType;
use Application\Enums\PersonType;
use Application\Enums\SiriusDocument;
use Application\Exceptions\SiriusApiException;
use Application\Forms\PostOfficeSearch;
use Application\Forms\PostOfficeSelect;
use Application\Helpers\RouteHelper;
use Application\Helpers\SendSiriusNoteHelper;
use Application\Helpers\SiriusDataProcessorHelper;
use Application\PostOffice\Country as PostOfficeCountry;
use Application\Services\SiriusApiService;
use DateTime;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Form\FormInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class FindPostOfficeBranchHandler implements RequestHandlerInterface
{
    use FormBuilder;

    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly RouteHelper $routeHelper,
        private readonly SendSiriusNoteHelper $sendNoteHelper,
        private readonly SiriusApiService $siriusApiService,
        private readonly SiriusDataProcessorHelper $siriusDataProcessorHelper,
        private readonly TemplateRendererInterface $renderer,
        private readonly array $config,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $template = 'application/pages/post_office/find_post_office_branch';

        $uuid = $request->getAttribute("uuid");

        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $formData = (array)($request->getParsedBody());
        $form = $this->createForm(PostOfficeSelect::class, $formData);
        $searchForm = $this->createForm(PostOfficeSearch::class, $formData);

        $searchString = $formData['searchString'] ?? $detailsData['address']['postcode'];

        $variables = [
            'details_data' => $detailsData,
            'form' => $form,
            'search_form' => $searchForm,
        ];

        if ($request->getMethod() === 'POST') {
            if (array_key_exists('confirmPostOffice', $formData)) {
                $systemType = $detailsData['personType'] === PersonType::Voucher
                    ? SiriusDocument::PostOfficeDocCheckVoucher
                    : SiriusDocument::PostOfficeDocCheckDonor;

                //trigger Post Office counter service & send pdf to sirius
                $counterService = $this->opgApiService->createYotiSession($uuid);
                $pdfData = $counterService['pdfBase64'];
                $pdf = $this->siriusApiService->sendDocument(
                    $detailsData,
                    $systemType,
                    $request,
                    $pdfData
                );

                if ($pdf['status'] !== 201) {
                    throw new SiriusApiException("Failed to send Post Office document.");
                }

                $this->sendNoteHelper->sendBlockedRoutesNote($detailsData, $request);

                return $this->routeHelper->toRedirect('root/po_what_happens_next', ['uuid' => $uuid]);
            }

            if (array_key_exists('selectPostoffice', $formData)) {
                $isValid = $form->isValid();
                $formArray = $form->getData(FormInterface::VALUES_AS_ARRAY);

                if ($isValid && is_array($formArray['postoffice'])) {
                    $this->opgApiService->addSelectedPostOffice($uuid, $formArray['postoffice']['fad_code']);

                    $postOfficeAddress = array_map('trim', explode(',', $formArray['postoffice']['address']));
                    $postOfficeAddress[] = $formArray['postoffice']['post_code'];

                    $variables['lpa_details'] = $this->siriusDataProcessorHelper->createLpaDetailsArray(
                        $detailsData,
                        $request,
                    );

                    $variables['formatted_dob'] = isset($detailsData['dob'])
                        ? (new DateTime($detailsData['dob']))->format("d F Y")
                        : 'Unknown';

                    $deadline = $this->opgApiService->estimatePostofficeDeadline($uuid);
                    $variables['deadline'] = (new DateTime($deadline))->format("d F Y");

                    if (! isset($detailsData['idMethod'])) {
                        throw new RuntimeException('ID Method has not been selected');
                    }

                    $variables['display_id_method'] = $this->getIdMethodForDisplay(
                        $this->config['opg_settings']['identity_documents'],
                        $detailsData['idMethod']
                    );
                    $variables['post_office_address'] = $postOfficeAddress;

                    $template = 'application/pages/post_office/confirm_post_office';
                }
            } else {
                $searchString = $formData['searchString'];
                $searchForm->isValid();
            }
        }

        $variables['post_office_list'] = $this->opgApiService->listPostOfficesByPostcode($uuid, $searchString);
        $variables['searchString'] = $searchString;

        return new HtmlResponse($this->renderer->render(
            $template,
            $variables,
        ));
    }

    private function getIdMethodForDisplay(array $options, array $idMethodArray): string
    {
        if (
            array_key_exists($idMethodArray['docType'], $options) &&
            $idMethodArray['idCountry'] === PostOfficeCountry::GBR->value
        ) {
            return $options[$idMethodArray['docType']];
        } else {
            $country = PostOfficeCountry::from($idMethodArray['idCountry'] ?? '');
            $idMethod = DocumentType::from($idMethodArray['docType'] ?? '');

            return sprintf('%s (%s)', $idMethod->translate(), $country->translate());
        }
    }
}

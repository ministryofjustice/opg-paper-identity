<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Enums\SiriusDocument;
use Application\Enums\LpaTypes;
use Application\Forms\Country;
use Application\Forms\CountryDocument;
use Application\Forms\IdMethod;
use Application\Forms\PassportDate;
use Application\Forms\PostOfficeSelect;
use Application\Forms\PostOfficeSearchLocation;
use Application\Helpers\FormProcessorHelper;
use Application\Helpers\SiriusDataProcessorHelper;
use Application\PostOffice\Country as PostOfficeCountry;
use Application\PostOffice\DocumentType;
use Application\PostOffice\DocumentTypeRepository;
use Application\Services\SiriusApiService;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Psr\Log\LoggerInterface;

class PostOfficeFlowController extends AbstractActionController
{
    use FormBuilder;

    protected $plugins;

    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly FormProcessorHelper $formProcessorHelper,
        private readonly SiriusApiService $siriusApiService,
        private readonly DocumentTypeRepository $documentTypeRepository,
        private readonly string $siriusPublicUrl,
        private readonly array $config,
    ) {
    }

    public function postOfficeDocumentsAction(): ViewModel|Response
    {
        $templates = ['default' => 'application/pages/post_office/post_office_documents'];
        $uuid = $this->params()->fromRoute("uuid");
        $dateSubForm = $this->createForm(PassportDate::class);
        $form = $this->createForm(IdMethod::class);
        $view = new ViewModel();

        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $view->setVariable('details_data', $detailsData);
        $view->setVariable('form', $form);

        if ($this->getRequest()->isPost()) {
            if (array_key_exists('check_button', $this->getRequest()->getPost()->toArray())) {
                $view->setVariable('date_sub_form', $dateSubForm);
                $formProcessorResponseDto = $this->formProcessorHelper->processPassportDateForm(
                    $uuid,
                    $this->getRequest()->getPost(),
                    $dateSubForm,
                    $templates
                );
                $view->setVariables($formProcessorResponseDto->getVariables());
            } else {
                if ($form->isValid()) {
                    $formData = $this->formToArray($form);

                    if ($formData['id_method'] == 'NONUKID') {
                        $this->opgApiService->updateIdMethodWithCountry($uuid, ['id_method' => $formData['id_method']]);
                        $redirect = "root/po_choose_country";
                    } else {
                        $this->opgApiService->updateIdMethodWithCountry($uuid, [
                            'id_method' => $formData['id_method'],
                            'id_country' => PostOfficeCountry::GBR->value,
                        ]);
                        switch ($detailsData["personType"]) {
                            case "voucher":
                                $redirect = "root/voucher_name";
                                break;
                            case "certificateProvider":
                                $redirect = "root/cp_name_match_check";
                                break;
                            default:
                                $redirect = "root/donor_details_match_check";
                                break;
                        }
                    }
                    $this->redirect()->toRoute($redirect, ['uuid' => $uuid]);
                }
            }
        }


        return $view->setTemplate($templates['default']);
    }

    public function chooseCountryAction(): ViewModel|Response
    {
        $templates = ['default' => 'application/pages/post_office/choose_country'];
        $uuid = $this->params()->fromRoute("uuid");
        $view = new ViewModel();
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $form = $this->createForm(Country::class);

        if ($this->getRequest()->isPost() && $form->isValid()) {
            $formData = $this->formToArray($form);

            $this->opgApiService->updateIdMethodWithCountry($uuid, $formData);

            return $this->redirect()->toRoute("root/po_choose_country_id", ['uuid' => $uuid]);
        }

        $countriesData = PostOfficeCountry::cases();
        $countriesData = array_filter(
            $countriesData,
            fn (PostOfficeCountry $country) => $country !== PostOfficeCountry::GBR
        );

        $view->setVariable('form', $form);
        $view->setVariable('countries_data', $countriesData);
        $view->setVariable('details_data', $detailsData);
        $view->setVariable('uuid', $uuid);

        return $view->setTemplate($templates['default']);
    }

    public function chooseCountryIdAction(): ViewModel|Response
    {
        $templates = ['default' => 'application/pages/post_office/choose_country_id'];
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        if (! isset($detailsData['idMethodIncludingNation']['id_country'])) {
            throw new \Exception("Country for document list has not been set.");
        }

        $country = PostOfficeCountry::from($detailsData['idMethodIncludingNation']['id_country']);

        $docs = $this->documentTypeRepository->getByCountry($country);

        $form = $this->createForm(CountryDocument::class);

        if ($this->getRequest()->isPost() && $form->isValid()) {
            $formData = $this->formToArray($form);
            $this->opgApiService->updateIdMethodWithCountry($uuid, $formData);

            switch ($detailsData["personType"]) {
                case "voucher":
                    $redirect = "root/voucher_name";
                    break;
                case "certificateProvider":
                    $redirect = "root/cp_name_match_check";
                    break;
                default:
                    $redirect = "root/donor_details_match_check";
                    break;
            }
            return $this->redirect()->toRoute($redirect, ['uuid' => $uuid]);
        }

        $view = new ViewModel([
            'form' => $form,
            'countryName' => $country->translate(),
            'details_data' => $detailsData,
            'supported_docs' => $docs,
        ]);

        return $view->setTemplate($templates['default']);
    }

    public function findPostOfficeBranchAction(): ViewModel|Response
    {
        $templates = [
            'default' => 'application/pages/post_office/find_post_office_branch',
            'confirm' => 'application/pages/post_office/confirm_post_office',
        ];
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");

        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $form = $this->createForm(PostOfficeSelect::class);
        $locationForm = $this->createForm(PostOfficeSearchLocation::class);

        $searchPostCode = $detailsData['address']['postcode'];

        $view->setVariable('details_data', $detailsData);

        if ($this->getRequest()->isPost()) {
            $formData = $this->getRequest()->getPost()->toArray();

            if (array_key_exists('confirmPostOffice', $formData)) {
                $counterService = $this->opgApiService->createYotiSession($uuid);
                $pdfData = $counterService['pdfBase64'];
                $pdf = $this->siriusApiService->sendDocument(
                    $detailsData,
                    SiriusDocument::PostOfficeDocCheck,
                    $this->getRequest(),
                    $pdfData
                );
                if ($pdf['status'] === 201) {
                    return $this->redirect()->toRoute('root/po_what_happens_next', ['uuid' => $uuid]);
                } else {
                    $template = $templates['confirm'];
                    $view->setVariable('errors', ['API Error']);
                }
            } elseif (array_key_exists('selectPostoffice', $formData)) {
                $processed = $this->formProcessorHelper->processPostOfficeSelectForm(
                    $uuid,
                    $form,
                    $templates,
                    $detailsData,
                    $this->config,
                    $this->getRequest()
                );
                $template = $processed->getTemplate();
                $view->setVariables($processed->getVariables());
            } elseif (array_key_exists('location', $formData)) {
                $processed = $this->formProcessorHelper->processPostOfficeSearchForm(
                    $uuid,
                    $locationForm,
                    $templates
                );
                $template = $processed->getTemplate();
                $view->setVariables($processed->getVariables());
            } else {
                $template = $templates['default'];
                $postOfficeData = $this->opgApiService->listPostOfficesByPostcode($uuid, $searchPostCode);
            }
        } else {
            $view->setVariable('form', $form);
            $view->setVariable('location_form', $locationForm);
            $template = $templates['default'];
            $postOfficeData = $this->opgApiService->listPostOfficesByPostcode($uuid, $searchPostCode);
            $view->setVariable('post_office_list', $postOfficeData);
            $view->setVariable('location', $searchPostCode);
        }

        return $view->setTemplate($template);
    }

    public function whatHappensNextAction(): ViewModel
    {
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $view->setVariable('details_data', $detailsData);

        $siriusUrl = $this->siriusPublicUrl . '/lpa/frontend/lpa/' . $detailsData["lpas"][0];

        $view->setVariables([
            'details_data', $detailsData,
            'sirius_url' => $siriusUrl
        ]);

        return $view->setTemplate('application/pages/post_office/what_happens_next');
    }

    public function postOfficeRouteNotAvailableAction(): ViewModel
    {
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $view->setVariable('details_data', $detailsData);

        return $view->setTemplate('application/pages/post_office/post_office_route_not_available');
    }
}

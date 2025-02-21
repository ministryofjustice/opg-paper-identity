<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Enums\SiriusDocument;
use Application\Enums\LpaTypes;
use Application\Exceptions\SiriusApiException;
use Application\Forms\Country;
use Application\Forms\CountryDocument;
use Application\Forms\IdMethod;
use Application\Forms\PassportDate;
use Application\Forms\PostOfficeSelect;
use Application\Forms\PostOfficeSearch;
use Application\Helpers\FormProcessorHelper;
use Application\PostOffice\Country as PostOfficeCountry;
use Application\PostOffice\DocumentType;
use Application\PostOffice\DocumentTypeRepository;
use Application\Services\SiriusApiService;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Form\FormInterface;
use DateTime;

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
        $template = $templates['default'];
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");

        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $form = $this->createForm(PostOfficeSelect::class);
        $searchForm = $this->createForm(PostOfficeSearch::class);

        $searchString = $detailsData['address']['postcode'];

        $view->setVariable('details_data', $detailsData);

        if ($this->getRequest()->isPost()) {
            $formData = $this->getRequest()->getPost()->toArray();

            if (array_key_exists('confirmPostOffice', $formData)) {
                return $this->processConfirmPostoffice($uuid, $detailsData);
            } elseif (array_key_exists('selectPostoffice', $formData)) {
                $template = $this->processSelectPostOffice(
                    $uuid,
                    $form,
                    $view,
                    $templates,
                    $detailsData,
                    $formData['searchString'] ?? $searchString
                );
            } elseif (array_key_exists('searchString', $formData)) {
                $template = $this->processSearchPostOffice(
                    $uuid,
                    $searchForm,
                    $view,
                    $templates
                );
            }
        } else {
            $view->setVariables([
                'form' => $form,
                'search_form' => $searchForm,
                'post_office_list' => $this->opgApiService->listPostOfficesByPostcode($uuid, $searchString),
                'searchString' => $searchString,
            ]);

            $template = $templates['default'];
        }

        return $view->setTemplate($template);
    }

    public function processConfirmPostoffice(
        string $uuid,
        array $detailsData,
    ): Response {

        $systemType = $detailsData['personType'] === 'voucher' ?
            SiriusDocument::PostOfficeDocCheckVoucher : SiriusDocument::PostOfficeDocCheckDonor;

        //trigger Post Office counter service & send pdf to sirius
        $counterService = $this->opgApiService->createYotiSession($uuid);
        $pdfData = $counterService['pdfBase64'];
        $pdf = $this->siriusApiService->sendDocument(
            $detailsData,
            $systemType,
            $this->getRequest(),
            $pdfData
        );
        if ($pdf['status'] !== 201) {
            throw new SiriusApiException("Failed to send Post Office document.");
        }
        return $this->redirect()->toRoute('root/po_what_happens_next', ['uuid' => $uuid]);
    }

    public function processSelectPostOffice(
        string $uuid,
        FormInterface $form,
        ViewModel $view,
        array $templates,
        array $detailsData,
        string $searchString
    ): string {

        if ($form->isValid()) {
            $formArray = $form->getData(FormInterface::VALUES_AS_ARRAY);

            $this->opgApiService->addSelectedPostOffice($uuid, $formArray['postoffice']['fad_code']);

            $lpaDetails = [];
            foreach ($detailsData['lpas'] as $lpa) {
                $lpasData = $this->siriusApiService->getLpaByUid($lpa, $this->getRequest());

                if (! empty($lpasData['opg.poas.lpastore'])) {
                    $lpaDetails[$lpa] = LpaTypes::fromName($lpasData['opg.poas.lpastore']['lpaType']);
                } else {
                    $lpaDetails[$lpa] = LpaTypes::fromName($lpasData['opg.poas.sirius']['caseSubtype']);
                }
            }

            $postOfficeAddress = array_map('trim', explode(',', $formArray['postoffice']['address']));
            $postOfficeAddress[] = $formArray['postoffice']['post_code'];

            $view->setVariables([
                'lpa_details' => $lpaDetails,
                'formatted_dob' => (new DateTime($detailsData['dob']))->format("d F Y"),
                'deadline' => (new DateTime($this->opgApiService->estimatePostofficeDeadline($uuid)))->format("d F Y"),
                'display_id_method' => $this->getIdMethodForDisplay(
                    $this->config['opg_settings']['identity_documents'],
                    $detailsData['idMethodIncludingNation']
                ),
                'post_office_address' => $postOfficeAddress,
            ]);
            $template = $templates['confirm'];
        } else {
            $template = $templates['default'];
            $view->setVariables([
                'searchString' => $searchString,
                'post_office_list' => $this->opgApiService->listPostOfficesByPostcode($uuid, $searchString),
            ]);
        }

        $view->setVariable('form', $form);

        return $template;
    }

    private static function getIdMethodForDisplay(array $options, array $idMethodArray): string
    {
        if (
            array_key_exists($idMethodArray['id_method'], $options) &&
            $idMethodArray['id_country'] === PostOfficeCountry::GBR->value
        ) {
            return $options[$idMethodArray['id_method']];
        } else {
            $country = PostOfficeCountry::from($idMethodArray['id_country'] ?? '');
            $idMethod = DocumentType::from($idMethodArray['id_method'] ?? '');
            return sprintf('%s (%s)', $idMethod->translate(), $country->translate());
        }
    }

    public function processSearchPostOffice(
        string $uuid,
        FormInterface $form,
        ViewModel $view,
        array $templates
    ): string {

        if ($form->isValid()) {
            $formArray = $form->getData(FormInterface::VALUES_AS_ARRAY);

            $view->setVariables([
                'searchString' => $formArray['searchString'],
                'post_office_list' => $this->opgApiService->listPostOfficesByPostcode(
                    $uuid,
                    $formArray['searchString']
                ),
            ]);
        }

        $view->setVariable('search_form', $form);

        return $templates['default'];
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

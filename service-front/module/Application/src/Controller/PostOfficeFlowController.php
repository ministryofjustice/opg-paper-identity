<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Enums\LpaTypes;
use Application\Forms\Country;
use Application\Forms\CountryDocument;
use Application\Forms\IdMethod;
use Application\Forms\PassportDatePo;
use Application\Forms\PostOfficeAddress;
use Application\Forms\PostOfficeSearchLocation;
use Application\Helpers\FormProcessorHelper;
use Application\PostOffice\Country as PostOfficeCountry;
use Application\PostOffice\DocumentType;
use Application\PostOffice\DocumentTypeRepository;
use Application\Services\SiriusApiService;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class PostOfficeFlowController extends AbstractActionController
{
    use FormBuilder;

    protected $plugins;

    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly FormProcessorHelper $formProcessorHelper,
        private readonly SiriusApiService $siriusApiService,
        private readonly DocumentTypeRepository $documentTypeRepository,
        private readonly array $config,
        private readonly string $siriusPublicUrl,
    ) {
    }

    public function postOfficeDocumentsAction(): ViewModel|Response
    {
        $templates = ['default' => 'application/pages/post_office/post_office_documents'];
        $uuid = $this->params()->fromRoute("uuid");
        $dateSubForm = $this->createForm(PassportDatePo::class);
        $form = $this->createForm(IdMethod::class);
        $view = new ViewModel();

        if (count($this->getRequest()->getPost())) {
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
                        return $this->redirect()->toRoute("root/donor_choose_country", ['uuid' => $uuid]);
                    } else {
                        $this->opgApiService->updateIdMethodWithCountry($uuid, [
                            'id_method' => $formData['id_method'],
                            'id_country' => PostOfficeCountry::GBR->value,
                        ]);
                        return $this->redirect()->toRoute("root/po_do_details_match", ['uuid' => $uuid]);
                    }
                }
            }
        }

        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $view->setVariable('details_data', $detailsData);

        return $view->setTemplate($templates['default']);
    }

    public function doDetailsMatchAction(): ViewModel
    {
        $uuid = $this->params()->fromRoute("uuid");

        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $detailsData['formatted_dob'] = (new \DateTime($detailsData['dob']))->format("d F Y");

        $view = new ViewModel();

        $siriusEditUrl = $this->siriusPublicUrl . '/lpa/frontend/lpa/' . $detailsData["lpas"][0];
        $view->setVariable('sirius_edit_url', $siriusEditUrl);
        $view->setVariable('details_data', $detailsData);
        $view->setVariable('uuid', $uuid);

        return $view->setTemplate('application/pages/post_office/donor_details_match_check');
    }

    public function findPostOfficeBranchAction(): ViewModel|Response
    {
        $templates = ['default' => 'application/pages/post_office/find_post_office_branch'];
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");

        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $form = $this->createForm(PostOfficeAddress::class);
        $locationForm = $this->createForm(PostOfficeSearchLocation::class);

        if (! isset($detailsData['searchPostcode'])) {
            $searchString = $detailsData['address']['postcode'];
        } else {
            $searchString = $detailsData['searchPostcode'];
        }

        $responseData = $this->opgApiService->listPostOfficesByPostcode($uuid, $searchString);
        $locationData = $this->formProcessorHelper->processPostOfficeSearchResponse($responseData);

        $view->setVariable('location', $searchString);
        $view->setVariable('post_office_list', $locationData);
        $view->setVariable('details_data', $detailsData);
        $view->setVariable('uuid', $uuid);

        if ($this->getRequest()->isPost()) {
            if (array_key_exists('location', $this->getRequest()->getPost()->toArray())) {
                $processed = $this->formProcessorHelper->processPostOfficeSearchForm(
                    $uuid,
                    $locationForm,
                    $templates
                );
            } else {
                $processed = $this->formProcessorHelper->processPostOfficeSelectForm(
                    $uuid,
                    $form,
                    $templates
                );
            }

            if (! is_null($processed->getRedirect())) {
                return $this->redirect()->toRoute($processed->getRedirect(), ['uuid' => $uuid]);
            }
            $view->setVariables($processed->getVariables());
        } else {
            $view->setVariable('form', $form);
            $view->setVariable('location_form', $locationForm);
        }

        return $view->setTemplate('application/pages/post_office/find_post_office_branch');
    }

    public function confirmPostOfficeAction(): ViewModel|Response
    {
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $optionsData = $this->config['opg_settings']['identity_documents'];
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $deadline = (new \DateTime($this->opgApiService->estimatePostofficeDeadline($uuid)))->format("d M Y");


        $postOfficeData = json_decode($detailsData["counterService"]["selectedPostOffice"] ?? '', true);

        $postOfficeAddress = explode(",", $postOfficeData['address']);
        $postOfficeAddress = array_merge($postOfficeAddress, [$postOfficeData['post_code']]);

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('uuid', $uuid);
        $view->setVariable('post_office_summary', true);
        $view->setVariable('post_office_address', $postOfficeAddress);
        $view->setVariable('deadline', $deadline);

        /**
         * @psalm-suppress PossiblyUndefinedArrayOffset
         */
        if (array_key_exists($detailsData['idMethodIncludingNation']['id_method'], $optionsData)) {
            $idMethodForDisplay = $optionsData[$detailsData['idMethodIncludingNation']['id_method']];
        } else {
            $country = PostOfficeCountry::from($detailsData['idMethodIncludingNation']['id_country'] ?? '');
            $idMethod = DocumentType::from($detailsData['idMethodIncludingNation']['id_method'] ?? '');
            $idMethodForDisplay = sprintf('%s (%s)', $idMethod->translate(), $country->translate());
        }

        $view->setVariable('display_id_method', $idMethodForDisplay);

        if ($this->getRequest()->isPost()) {
            $this->opgApiService->confirmSelectedPostOffice($uuid, $deadline);

            //trigger Post Office counter service & send pdf to sirius
            $counterService = $this->opgApiService->createYotiSession($uuid);
            $pdfData = $counterService['pdfBase64'];
            /**
             * @psalm-suppress ArgumentTypeCoercion
             */
            $pdf = $this->siriusApiService->sendPostOfficePDf($pdfData, $detailsData, $this->request);

            if ($pdf['status'] === 201) {
                return $this->redirect()->toRoute('root/what_happens_next', ['uuid' => $uuid]);
            } else {
                $view->setVariable('errors', ['API Error']);
            }
        }

        return $view->setTemplate('application/pages/post_office/confirm_post_office');
    }

    public function whatHappensNextAction(): ViewModel
    {
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $view->setVariable('details_data', $detailsData);

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

    public function donorLpaCheckAction(): ViewModel
    {
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $view = new ViewModel();
        $lpaDetails = [];

        foreach ($detailsData['lpas'] as $lpa) {
            /**
             * @psalm-suppress ArgumentTypeCoercion
             */
            $lpasData = $this->siriusApiService->getLpaByUid($lpa, $this->request);

            if (! empty($lpasData['opg.poas.lpastore'])) {
                $name = $lpasData['opg.poas.lpastore']['donor']['firstNames'] . " " .
                    $lpasData['opg.poas.lpastore']['donor']['lastName'];

                $type = LpaTypes::fromName($lpasData['opg.poas.lpastore']['lpaType']);
            } else {
                $name = $lpasData['opg.poas.sirius']['donor']['firstname'] . " " .
                    $lpasData['opg.poas.sirius']['donor']['surname'];

                $type = LpaTypes::fromName($lpasData['opg.poas.sirius']['caseSubtype']);
            }

            $lpaDetails[$lpa] = [
                'name' => $name,
                'type' => $type,
            ];
        }

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('lpa_details', $lpaDetails);
        $view->setVariable('lpa_count', count($detailsData['lpas']));

        return $view->setTemplate('application/pages/post_office/donor_lpa_check');
    }

    public function removeLpaAction(): Response
    {
        $uuid = $this->params()->fromRoute("uuid");
        $lpa = $this->params()->fromRoute("lpa");

        $this->opgApiService->updateCaseWithLpa($uuid, $lpa, true);

        return $this->redirect()->toRoute("root/po_donor_lpa_check", ['uuid' => $uuid]);
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

            return $this->redirect()->toRoute("root/donor_choose_country_id", ['uuid' => $uuid]);
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

            return $this->redirect()->toRoute("root/donor_details_match_check", ['uuid' => $uuid]);
        }

        $view = new ViewModel([
            'form' => $form,
            'countryName' => $country->translate(),
            'details_data' => $detailsData,
            'supported_docs' => $docs,
        ]);

        return $view->setTemplate($templates['default']);
    }
}

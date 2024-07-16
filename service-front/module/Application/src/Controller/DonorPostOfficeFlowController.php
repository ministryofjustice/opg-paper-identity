<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Enums\LpaTypes;
use Application\Forms\PassportDatePo;
use Application\Forms\PostOfficeAddress;
use Application\Forms\PostOfficePostcode;
use Application\Forms\PostOfficeSearchLocation;
use Application\Helpers\FormProcessorHelper;
use Application\Services\SiriusApiService;
use Laminas\Form\Annotation\AttributeBuilder;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Validator\NotEmpty;
use Laminas\View\Model\ViewModel;

class DonorPostOfficeFlowController extends AbstractActionController
{
    protected $plugins;

    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly FormProcessorHelper $formProcessorHelper,
        private readonly SiriusApiService $siriusApiService,
        private readonly array $config,
    ) {
    }

    public function postOfficeDocumentsAction(): ViewModel|Response
    {
        $templates = ['default' => 'application/pages/post_office/post_office_documents'];
        $uuid = $this->params()->fromRoute("uuid");
        $dateSubForm = (new AttributeBuilder())->createForm(PassportDatePo::class);
        $view = new ViewModel();

        if (count($this->getRequest()->getPost())) {
            $formData = $this->getRequest()->getPost()->toArray();

            if (array_key_exists('check_button', $formData)) {
                $dateSubForm->setData($formData);
                $view->setVariable('date_sub_form', $dateSubForm);
                $formProcessorResponseDto = $this->formProcessorHelper->processPassportDateForm(
                    $uuid,
                    $this->getRequest()->getPost(),
                    $dateSubForm,
                    $templates
                );
                $view->setVariables($formProcessorResponseDto->getVariables());
            } else {
                $this->opgApiService->updateIdMethod($uuid, $formData['id_method']);
                return $this->redirect()->toRoute("root/po_do_details_match", ['uuid' => $uuid]);
            }
        }

        $optionsdata = $this->config['opg_settings']['post_office_identity_methods'];
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $view->setVariable('options_data', $optionsdata);
        $view->setVariable('details_data', $detailsData);
        $view->setVariable('uuid', $uuid);

        return $view->setTemplate($templates['default']);
    }

    public function doDetailsMatchAction(): ViewModel
    {
        $uuid = $this->params()->fromRoute("uuid");

        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $detailsData['formatted_dob'] = (new \DateTime($detailsData['dob']))->format("d F Y");

        $view = new ViewModel();

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('uuid', $uuid);

        return $view->setTemplate('application/pages/post_office/donor_details_match_check');
    }

    public function findPostOfficeBranchAction(): ViewModel|Response
    {
        $templates = ['default' => 'application/pages/post_office/find_post_office_branch'];
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");

        $optionsdata = $this->config['opg_settings']['post_office_identity_methods'];
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $form = (new AttributeBuilder())->createForm(PostOfficeAddress::class);
        $locationForm = (new AttributeBuilder())->createForm(PostOfficeSearchLocation::class);

        if (! isset($detailsData['searchPostcode'])) {
            $searchString = $detailsData['address']['postcode'];
        } else {
            $searchString = $detailsData['searchPostcode'];
        }

        $responseData = $this->opgApiService->listPostOfficesByPostcode($uuid, $searchString);
        $locationData = $this->formProcessorHelper->processPostOfficeSearchResponse($responseData);

        $view->setVariable('location', $searchString);
        $view->setVariable('post_office_list', $locationData);
        $view->setVariable('options_data', $optionsdata);
        $view->setVariable('details_data', $detailsData);
        $view->setVariable('uuid', $uuid);

        if (count($this->getRequest()->getPost())) {
            if ($this->getRequest()->getPost('postoffice') == 'none') {
                return $this->redirect()->toRoute('root/post_office_route_not_available', ['uuid' => $uuid]);
            }

            if (array_key_exists('location', $this->getRequest()->getPost()->toArray())) {
                $processed = $this->formProcessorHelper->processPostOfficeSearchForm(
                    $uuid,
                    $this->getRequest()->getPost(),
                    $locationForm,
                    $templates
                );
            } else {
                $processed = $this->formProcessorHelper->processPostOfficeSearchForm(
                    $uuid,
                    $this->getRequest()->getPost(),
                    $form,
                    $templates
                );
                if ($processed->getRedirect() !== null) {
                    return $this->redirect()->toRoute($processed->getRedirect(), ['uuid' => $uuid]);
                }
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
        $optionsdata = $this->config['opg_settings']['post_office_identity_methods'];
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $date = new \DateTime();
        $date->modify("+90 days");
        $deadline = $date->format("d M Y");

        $postOfficeData = json_decode($detailsData['selectedPostOffice'], true);

        $postOfficeAddress = explode(",", $postOfficeData['address']);
        $postOfficeAddress = array_merge($postOfficeAddress, [$postOfficeData['postcode']]);

        $view->setVariable('options_data', $optionsdata);
        $view->setVariable('details_data', $detailsData);
        $view->setVariable('uuid', $uuid);
        $view->setVariable('post_office_summary', true);
        $view->setVariable('post_office_address', $postOfficeAddress);
        $view->setVariable('deadline', $deadline);
        $view->setVariable('id_method', $optionsdata[$detailsData['idMethod']]);

        if (count($this->getRequest()->getPost())) {
            $responseData = $this->opgApiService->confirmSelectedPostOffice($uuid, $deadline);
            if ($responseData['result'] == 'Updated') {
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
            /**
             * @psalm-suppress PossiblyNullArrayAccess
             */
            $name = $lpasData['opg.poas.lpastore']['donor']['firstNames'] . " " .
                $lpasData['opg.poas.lpastore']['donor']['lastName'];

            /**
             * @psalm-suppress PossiblyNullArrayAccess
             * @psalm-suppress InvalidArrayOffset
             * @psalm-suppress PossiblyNullArgument
             */
            $type = LpaTypes::fromName($lpasData['opg.poas.lpastore']['lpaType']);

            $lpaDetails[$lpa] = [
                'name' => $name,
                'type' => $type
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
}

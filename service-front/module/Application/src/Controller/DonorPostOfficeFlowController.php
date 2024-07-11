<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Forms\PassportDatePo;
use Application\Forms\PostOfficeNumericCode;
use Application\Forms\PostOfficePostcode;
use Application\Forms\PostOfficeSearchLocation;
use Application\Helpers\FormProcessorHelper;
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

    public function findPostOfficeAction(): ViewModel|Response
    {
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");

        $form = (new AttributeBuilder())->createForm(PostOfficePostcode::class);
        $view->setVariable('form', $form);

        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $optionsdata = $this->config['opg_settings']['post_office_identity_methods'];

        foreach ($detailsData['address'] as $line) {
            if (preg_match('/^[A-Z]{1,2}[0-9]{1,2}[A-Z]? [0-9][A-Z]{2}$/', $line)) {
                $defaultPostcode = $line;
            }
        }

        if (count($this->getRequest()->getPost())) {
            $formObject = $this->getRequest()->getPost();
            $formData = $formObject->toArray();
            if ($formData['postcode'] == 'alt') {
                $postcode = $formData['alt_postcode'];
            } else {
                $postcode = $formData['postcode'];
            }
            $formObject->set('selected_postcode', $postcode);
            $form->setData($formObject);

            if ($form->isValid()) {
                $response = $this->opgApiService->addSearchPostcode($uuid, $postcode);
                if ($response['result'] === 'Updated') {
                    return $this->redirect()->toRoute('root/find_post_office_branch', ['uuid' => $uuid]);
                }
            }
        }

        /**
         * @psalm-suppress PossiblyUndefinedVariable
         */
        $view->setVariable('default_postcode', $defaultPostcode);
        $view->setVariable('options_data', $optionsdata);
        $view->setVariable('details_data', $detailsData);
        $view->setVariable('uuid', $uuid);

        return $view->setTemplate('application/pages/post_office/find_post_office');
    }

    public function findPostOfficeBranchAction(): ViewModel|Response
    {
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");

        $optionsdata = $this->config['opg_settings']['post_office_identity_methods'];
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $form = (new AttributeBuilder())->createForm(PostOfficeNumericCode::class);
        $locationForm = (new AttributeBuilder())->createForm(PostOfficeSearchLocation::class);
        $view->setVariable('form', $form);
        $view->setVariable('location_form', $locationForm);
        
        if (! isset($detailsData['searchPostcode'])) {
            $searchPostcode = $detailsData['address']['postcode'];
        } else {
            $searchPostcode = $detailsData['searchPostcode'];
        }

        $responseData = $this->opgApiService->listPostOfficesByPostcode($uuid, $searchPostcode);
        $view->setVariable('post_office_list', $responseData);

        if (count($this->getRequest()->getPost())) {
            $formData = $this->getRequest()->getPost();
            $formArray = $formData->toArray();

            if ($this->getRequest()->getPost('postoffice') == 'none') {
                return $this->redirect()->toRoute('root/post_office_route_not_available', ['uuid' => $uuid]);
            }

            if (array_key_exists('location', $formArray)) {
                $locationForm->setData(['location' => $formArray['location']]);
                if ($locationForm->isValid()) {
                    $responseData = $this->opgApiService->searchPostOfficesByLocation($uuid, $formData['location']);
                    $view->setVariable('post_office_list', $responseData);
                } else {
                    $locationForm->setMessages(['location' => ['Please enter a postcode, town or street name']]);
                }
            } else {
                $responseData = $this->opgApiService->addSelectedPostOffice($uuid, $formData['postoffice']);

                if ($responseData['result'] == 'Updated') {
                    return $this->redirect()->toRoute('root/confirm_post_office', ['uuid' => $uuid]);
                } else {
                    $form->setMessages(['Error saving Post Office to this case.']);
                }
            }
        }

        $view->setVariable('options_data', $optionsdata);
        $view->setVariable('details_data', $detailsData);
        $view->setVariable('uuid', $uuid);

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

        $responseData = $this->opgApiService->getPostOfficeByCode($uuid, (int)$detailsData['selectedPostOffice']);
        $postOfficeAddress = explode(",", $responseData['address']);

        $view->setVariable('options_data', $optionsdata);
        $view->setVariable('details_data', $detailsData);
        $view->setVariable('uuid', $uuid);
        $view->setVariable('post_office_summary', true);
        $view->setVariable('post_office_data', $responseData);
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

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('lpas', $detailsData['lpas']);
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

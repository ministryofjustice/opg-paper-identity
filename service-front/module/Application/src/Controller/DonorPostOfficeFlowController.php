<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Forms\PostOfficeNumericCode;
use Application\Forms\PostOfficePostcode;
use Application\Helpers\FormProcessorHelper;
use Laminas\Form\Annotation\AttributeBuilder;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class DonorPostOfficeFlowController extends AbstractActionController
{
    protected $plugins;

    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly FormProcessorHelper $formProcessorHellper,
        private readonly array $config,
    ) {
    }

    public function postOfficeDocumentsAction(): ViewModel|Response
    {
        $uuid = $this->params()->fromRoute("uuid");

        if (count($this->getRequest()->getPost())) {
            $formData = $this->getRequest()->getPost()->toArray();
            $this->opgApiService->updateIdMethod($uuid, $formData['id_method']);
            return $this->redirect()->toRoute("root/find_post_office", ['uuid' => $uuid]);
        }

        $optionsdata = $this->config['opg_settings']['post_office_identity_methods'];
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $view = new ViewModel();

        $view->setVariable('options_data', $optionsdata);
        $view->setVariable('details_data', $detailsData);
        $view->setVariable('uuid', $uuid);

        return $view->setTemplate('application/pages/post_office/post_office_documents');
    }

    public function findPostOfficeAction(): ViewModel
    {
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");

        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $optionsdata = $this->config['opg_settings']['post_office_identity_methods'];
        $postcode = "";
        foreach ($detailsData['address'] as $line) {
            if (preg_match('/^[A-Z]{1,2}[0-9]{1,2}[A-Z]? [0-9][A-Z]{2}$/', $line)) {
                $postcode = $line;
            }
        }

        $view->setVariable('postcode', $postcode);
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

        if (count($this->getRequest()->getPost())) {
            if ($this->getRequest()->getPost('postoffice') == 'none') {
                return $this->redirect()->toRoute('root/post_office_route_not_available', ['uuid' => $uuid]);
            }

            $form = (new AttributeBuilder())->createForm(PostOfficePostcode::class);

            $formProcessorResponseDto = $this->formProcessorHellper->processFindPostOffice(
                $uuid,
                $optionsdata,
                $form,
                $this->getRequest()->getPost(),
                $detailsData
            );

            foreach ($formProcessorResponseDto->getVariables() as $key => $variable) {
                $view->setVariable($key, $variable);
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

        if (count($this->getRequest()->getPost())) {
            if ($this->getRequest()->getPost('postoffice') == 'none') {
                return $this->redirect()->toRoute('root/post_office_route_not_available', ['uuid' => $uuid]);
            }

            $form = (new AttributeBuilder())->createForm(PostOfficeNumericCode::class);

            $formProcessorResponseDto = $this->formProcessorHellper->processFindPostOffice(
                $uuid,
                $optionsdata,
                $form,
                $this->getRequest()->getPost(),
                $detailsData
            );
            foreach ($formProcessorResponseDto->getVariables() as $key => $variable) {
                $view->setVariable($key, $variable);
            }
        }

        $view->setVariable('options_data', $optionsdata);
        $view->setVariable('details_data', $detailsData);
        $view->setVariable('uuid', $uuid);

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
}

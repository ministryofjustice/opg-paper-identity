<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Services\FormProcessorService;
use Application\Services\SiriusApiService;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\Controller\Plugin\Redirect;
use Laminas\View\Model\ViewModel;
use Laminas\Form\Annotation\AttributeBuilder;

class DonorPostOfficeFlowController extends AbstractActionController
{
    protected $plugins;

    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly SiriusApiService $siriusApiService,
        private readonly FormProcessorService $formProcessorService,
        private readonly array $config,
    ) {
    }

    public function postOfficeDocumentsAction(): ViewModel|Response
    {
        $uuid = $this->params()->fromRoute("uuid");

        if (count($this->getRequest()->getPost())) {
            $formData = $this->getRequest()->getPost()->toArray();
            $response = $this->opgApiService->updateIdMethod($uuid, $formData['id_method']);

            if ($response === "Updated") {
                return $this->redirect()->toRoute("donor_details_match_check", ['uuid' => $uuid]);
            }
        }

        $optionsdata = $this->config['opg_settings']['post_office_identity_methods'];
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $view = new ViewModel();

        $view->setVariable('options_data', $optionsdata);
        $view->setVariable('details_data', $detailsData);
        $view->setVariable('uuid', $uuid);

        return $view->setTemplate('application/pages/post_office/post_office_documents');
    }
}

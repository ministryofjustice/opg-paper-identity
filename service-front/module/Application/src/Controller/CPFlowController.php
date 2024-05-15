<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Forms\LpaReferenceNumber;
use Application\Helpers\FormProcessorHelper;
use Laminas\Form\Annotation\AttributeBuilder;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class CPFlowController extends AbstractActionController
{
    protected $plugins;
    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly FormProcessorHelper    $formProcessorHellper,
        private readonly array                  $config,
    ) {
    }

    public function howWillCpConfirmAction(): ViewModel|Response
    {
        $uuid = $this->params()->fromRoute("uuid");

        if (count($this->getRequest()->getPost())) {
            $formData = $this->getRequest()->getPost()->toArray();
            $this->opgApiService->updateIdMethod($uuid, $formData['id_method']);
            return $this->redirect()->toRoute("cp_does_name_match_id", ['uuid' => $uuid]);


//
//            switch ($formData['id_method']) {
//                case 'pn':
//                    $this->redirect()
//                        ->toRoute("passport_number", ['uuid' => $uuid]);
//                    break;
//
//                case 'dln':
//                    $this->redirect()
//                        ->toRoute("driving_licence_number", ['uuid' => $uuid]);
//                    break;
//
//                case 'nin':
//                    $this->redirect()
//                        ->toRoute("national_insurance_number", ['uuid' => $uuid]);
//                    break;
//
//                default:
//                    break;
//            }
        }

        $optionsdata = $this->config['opg_settings']['identity_methods'];
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $view = new ViewModel();

        $view->setVariable('options_data', $optionsdata);
        $view->setVariable('details_data', $detailsData);
        $view->setVariable('uuid', $uuid);

        return $view->setTemplate('application/pages/cp/how_will_the_cp_confirm');
    }

    public function doesNameMatchIdAction(): ViewModel
    {
        $uuid = $this->params()->fromRoute("uuid");
        $optionsdata = $this->config['opg_settings']['identity_methods'];
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $view = new ViewModel();

        $view->setVariable('options_data', $optionsdata);
        $view->setVariable('details_data', $detailsData);

        return $view->setTemplate('application/pages/cp/cp_id_check');
    }

    public function confirmLpasAction(): ViewModel
    {
        $uuid = $this->params()->fromRoute("uuid");
        $lpas = $this->opgApiService->getLpasByDonorData();

        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $view = new ViewModel();

        $view->setVariable('lpas', $lpas);
        $view->setVariable('details_data', $detailsData);
        $view->setVariable('case_uuid', $uuid);

        return $view->setTemplate('application/pages/cp/confirm_lpas');
    }

    public function addLpaAction(): ViewModel
    {
        $templates = [
            'default' => 'application/pages/cp/add_lpa',
        ];
        $uuid = $this->params()->fromRoute("uuid");
        $lpas = $this->opgApiService->getLpasByDonorData();
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $form = (new AttributeBuilder())->createForm(LpaReferenceNumber::class);

        $view = new ViewModel();
        $view->setVariable('lpas', $lpas);
        $view->setVariable('details_data', $detailsData);
        $view->setVariable('form', $form);
        $view->setVariable('case_uuid', $uuid);

        if (count($this->getRequest()->getPost())) {
            $processed = $this->formProcessorHellper->findLpa(
                $uuid,
                $this->getRequest()->getPost(),
                $form,
                $templates
            );

            foreach ($processed->getVariables() as $key => $variable) {
                $view->setVariable($key, $variable);
            }
            $view->setVariable('form', $processed->getForm());
            return $view->setTemplate($processed->getTemplate());
        }
        return $view->setTemplate($templates['default']);
    }
}

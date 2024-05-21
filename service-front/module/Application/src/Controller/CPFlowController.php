<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Forms\BirthDate;
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
        private readonly FormProcessorHelper $formProcessorHellper,
        private readonly array $config,
    ) {
    }

    public function howWillCpConfirmAction(): ViewModel|Response
    {
        $uuid = $this->params()->fromRoute("uuid");

        if (count($this->getRequest()->getPost())) {
            $formData = $this->getRequest()->getPost()->toArray();
            $this->opgApiService->updateIdMethod($uuid, $formData['id_method']);
            return $this->redirect()->toRoute("root/cp_name_match_check", ['uuid' => $uuid]);


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

    public function nameMatchCheckAction(): ViewModel
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

    public function addLpaAction(): ViewModel|Response
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
            $formObject = $this->getRequest()->getPost();

            if($formObject->get('lpa')) {
                $processed = $this->formProcessorHellper->findLpa(
                    $uuid,
                    $this->getRequest()->getPost(),
                    $form,
                    $templates
                );
                $view->setVariables($processed->getVariables());
                $view->setVariable('form', $processed->getForm());
                return $view->setTemplate($processed->getTemplate());
            } else {
                $responseData = $this->opgApiService->updateCaseWithLpa($uuid, $formObject->get('add_lpa_number'));

                if($responseData['result'] === 'Updated') {
                    return $this->redirect()->toRoute('root/cp_confirm_lpas', ['uuid' => $uuid]);
                }
            }
        }
        return $view->setTemplate($templates['default']);
    }

    public function confirmDobAction(): ViewModel|Response
    {
        $view = new ViewModel();
        $templates = [
            'default' => 'application/pages/cp/confirm_dob',
        ];
        $uuid = $this->params()->fromRoute("uuid");
        $form = (new AttributeBuilder())->createForm(BirthDate::class);

        if (count($this->getRequest()->getPost())) {
            $params = $this->getRequest()->getPost();
            $date = sprintf(
                "%s-%s-%s",
                $params->get('dob_year'),
                $params->get('dob_month'),
                $params->get('dob_day'),
            );
            $params->set('date', $date);
            $form->setData($params);

            if ($form->isValid()) {
                echo json_encode($form->getData());
                return $this->redirect()->toRoute('root/cp_confirm_address', ['uuid' => $uuid]);
            }
            $view->setVariable('form', $form);
        }

        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $view->setVariable('details_data', $detailsData);

        return $view->setTemplate($templates['default']);
    }

    public function confirmAddressAction(): ViewModel|Response
    {
        $view = new ViewModel();
        $form = (new AttributeBuilder())->createForm(BirthDate::class);
        $templates = [
            'default' => 'application/pages/cp/confirm_address_match',
        ];
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $view->setVariable('details_data', $detailsData);

        echo json_encode($detailsData);
        if (count($this->getRequest()->getPost())) {
            $params = $this->getRequest()->getPost();
            $form->setData($params);

            if ($form->isValid()) {
                echo json_encode($form->getData());
                return $this->redirect()->toRoute('root/cp_confirm_address', ['uuid' => $uuid]);
            }
            $view->setVariable('form', $form);
        }

        return $view->setTemplate($templates['default']);
    }
}

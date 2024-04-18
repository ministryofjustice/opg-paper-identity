<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Forms\DrivingLicenceNumber;
use Application\Forms\PassportNumber;
use Application\Forms\PassportDate;
use Application\Services\FormProcessorService;
use Application\Services\SiriusApiService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Form\Annotation\AttributeBuilder;
use Application\Forms\NationalInsuranceNumber;

class CPFlowController extends AbstractActionController
{
    protected $plugins;
    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly SiriusApiService $siriusApiService,
        private readonly FormProcessorService $formProcessorService,
        private readonly array $config,
    ) {
    }

    public function startAction(): ViewModel
    {
        $lpasQuery = $this->params()->fromQuery("lpas");
        $lpas = [];
        foreach ($this->params()->fromQuery("lpas") as $lpaUid) {
            $data = $this->siriusApiService->getLpaByUid($lpaUid, $this->getRequest());
            $lpas[] = $data['opg.poas.lpastore'];
        }

        $detailsData = $this->opgApiService->stubDetailsResponse();

        // Find the details of the actor (donor or certificate provider, based on URL) that we need to ID check them

        // Create a case in the API with the LPA UID and the actors' details

        // Redirect to the "select which ID to use" page for this case
        $firstName = $detailsData['FirstName'];
        $lastName = $detailsData['LastName'];
        $type = $this->params()->fromQuery("personType");
        $dob = (new \DateTime($detailsData['DOB']))->format("Y-m-d");

        // Find the details of the actor (donor or certificate provider, based on URL) that we need to ID check them

        // Create a case in the API with the LPA UID and the actors' details

        // Redirect to the "select which ID to use" page for this case

        $case = $this->opgApiService->createCase($firstName, $lastName, $dob, $type, $lpasQuery);

        $types = [
            'donor' => 'Donor',
            'cp' => 'Certificate Provider'
        ];

        $view = new ViewModel([
            'lpaUids' => $this->params()->fromQuery("lpas"),
            'type' => $types[$this->params()->fromQuery("personType")],
            'lpas' => $lpas,
            'case' => $case['uuid'],
            'details' => $detailsData,
        ]);

        return $view->setTemplate('application/pages/cp_start');
    }

    public function howWillCpConfirmAction(): ViewModel
    {
        $uuid = $this->params()->fromRoute("uuid");

        if (count($this->getRequest()->getPost())) {
            $formData = $this->getRequest()->getPost()->toArray();

            switch ($formData['id_method']) {
                case 'pn':
                    $this->redirect()
                        ->toRoute("passport_number", ['uuid' => $uuid]);
                    break;

                case 'dln':
                    $this->redirect()
                        ->toRoute("driving_licence_number", ['uuid' => $uuid]);
                    break;

                case 'nin':
                    $this->redirect()
                        ->toRoute("national_insurance_number", ['uuid' => $uuid]);
                    break;

                default:
                    break;
            }
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
        $view->setVariable('details', $detailsData);

        return $view->setTemplate('application/pages/cp/confirm_lpas');
    }

    public function confirmDob(): ViewModel
    {
        $uuid = $this->params()->fromRoute("uuid");
        $lpas = $this->opgApiService->getLpasByDonorData();

        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $view = new ViewModel();

        $view->setVariable('lpas', $lpas);
        $view->setVariable('details', $detailsData);

        return $view->setTemplate('application/pages/cp/confirm_lpas');
    }
//
//    public function addressVerificationAction(): ViewModel
//    {
//        $data = $this->opgApiService->getAddressVerificationData();
//
//        $view = new ViewModel();
//
//        $view->setVariable('options_data', $data);
//
//        return $view->setTemplate('application/pages/address_verification');
//    }

    public function nationalInsuranceNumberAction(): ViewModel
    {
        $templates = [
            'default' => 'application/pages/national_insurance_number',
            'success' => 'application/pages/national_insurance_number_success',
            'fail' => 'application/pages/national_insurance_number_fail'
        ];
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $view->setVariable('uuid', $uuid);

        $form = (new AttributeBuilder())->createForm(NationalInsuranceNumber::class);
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('form', $form);

        if (count($this->getRequest()->getPost())) {
            return $this->formProcessorService->processNationalInsuranceNumberForm(
                $this->getRequest()->getPost(),
                $form,
                $view,
                $templates
            );
        }

        return $view->setTemplate($templates['default']);
    }
//
//    public function drivingLicenceNumberAction(): ViewModel
//    {
//        $templates = [
//            'default' => 'application/pages/driving_licence_number',
//            'success' => 'application/pages/driving_licence_number_success',
//            'fail' => 'application/pages/driving_licence_number_fail'
//        ];
//        $view = new ViewModel();
//        $uuid = $this->params()->fromRoute("uuid");
//        $view->setVariable('uuid', $uuid);
//
//        $form = (new AttributeBuilder())->createForm(DrivingLicenceNumber::class);
//        $detailsData = $this->opgApiService->getDetailsData();
//
//        $view->setVariable('details_data', $detailsData);
//        $view->setVariable('form', $form);
//
//        if (count($this->getRequest()->getPost())) {
//            return $this->formProcessorService->processDrivingLicencenForm(
//                $this->getRequest()->getPost(),
//                $form,
//                $view,
//                $templates
//            );
//        }
//
//        return $view->setTemplate($templates['default']);
//    }
//
//    public function passportNumberAction(): ViewModel
//    {
//        $templates = [
//            'default' => 'application/pages/passport_number',
//            'success' => 'application/pages/passport_number_success',
//            'fail' => 'application/pages/passport_number_fail'
//        ];
//        $view = new ViewModel();
//        $uuid = $this->params()->fromRoute("uuid");
//        $view->setVariable('uuid', $uuid);
//
//        $form = (new AttributeBuilder())->createForm(PassportNumber::class);
//        $dateSubForm = (new AttributeBuilder())->createForm(PassportDate::class);
//        $detailsData = $this->opgApiService->getDetailsData();
//
//        $view->setVariable('details_data', $detailsData);
//        $view->setVariable('form', $form);
//        $view->setVariable('date_sub_form', $dateSubForm);
//        $view->setVariable('details_open', false);
//
//        if (count($this->getRequest()->getPost())) {
//            $formData = $this->getRequest()->getPost();
//            $data = $formData->toArray();
//            $view->setVariable('passport', $data['passport']);
//
//            if (array_key_exists('check_button', $formData->toArray())) {
//                return $this->formProcessorService->processPassportDateForm(
//                    $this->getRequest()->getPost(),
//                    $dateSubForm,
//                    $view,
//                    $templates
//                );
//            } else {
//                $view->setVariable('passport_indate', $data['inDate']);
//                return $this->formProcessorService->processPassportForm(
//                    $this->getRequest()->getPost(),
//                    $form,
//                    $view,
//                    $templates
//                );
//            }
//        }
//
//        return $view->setTemplate($templates['default']);
//    }
//
//    public function identityCheckPassedAction(): ViewModel
//    {
//        $lpasData = $this->opgApiService->getLpasByDonorData();
//        $detailsData = $this->opgApiService->getDetailsData();
//
//        $view = new ViewModel();
//
//        $view->setVariable('lpas_data', $lpasData);
//        $view->setVariable('details_data', $detailsData);
//
//        return $view->setTemplate('application/pages/identity_check_passed');
//    }
//
//    public function identityCheckFailedAction(): ViewModel
//    {
//        $lpasData = $this->opgApiService->getLpasByDonorData();
//        $detailsData = $this->opgApiService->getDetailsData();
//
//        $view = new ViewModel();
//
//        $view->setVariable('lpas_data', $lpasData);
//        $view->setVariable('details_data', $detailsData);
//
//        return $view->setTemplate('application/pages/identity_check_failed');
//    }
//
//    public function thinFileFailureAction(): ViewModel
//    {
//        $view = new ViewModel();
//
//        return $view->setTemplate('application/pages/thin_file_failure');
//    }
}

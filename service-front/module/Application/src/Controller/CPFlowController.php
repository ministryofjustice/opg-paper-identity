<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Forms\BirthDate;
use Application\Forms\CpAltAddress;
use Application\Forms\DrivingLicenceNumber;
use Application\Forms\LpaReferenceNumber;
use Application\Forms\NationalInsuranceNumber;
use Application\Forms\PassportDate;
use Application\Forms\PassportDateCp;
use Application\Forms\PassportNumber;
use Application\Forms\Postcode;
use Application\Forms\PostOfficePostcode;
use Application\Helpers\AddressProcessorHelper;
use Application\Helpers\FormProcessorHelper;
use Application\Helpers\LpaFormHelper;
use Laminas\Form\Annotation\AttributeBuilder;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Application\Services\SiriusApiService;

class CPFlowController extends AbstractActionController
{
    protected $plugins;
    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly FormProcessorHelper $formProcessorHelper,
        private readonly SiriusApiService $siriusApiService,
        private readonly AddressProcessorHelper $addressProcessorHelper,
        private readonly LpaFormHelper $lpaFormHelper,
        private readonly array $config,
    ) {
    }

    public function howWillCpConfirmAction(): ViewModel|Response
    {
        $templates = [
            'default' => 'application/pages/cp/how_will_the_cp_confirm'
        ];
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $dateSubForm = (new AttributeBuilder())->createForm(PassportDateCp::class);
        $view->setVariable('date_sub_form', $dateSubForm);

        if (count($this->getRequest()->getPost())) {
            $formData = $this->getRequest()->getPost()->toArray();
            if (array_key_exists('check_button', $formData)) {
                $dateSubForm->setData($this->getRequest()->getPost());
                $formProcessorResponseDto = $this->formProcessorHelper->processPassportDateForm(
                    $uuid,
                    $this->getRequest()->getPost(),
                    $dateSubForm,
                    $templates
                );
                $view->setVariables($formProcessorResponseDto->getVariables());
            } else {
                $this->opgApiService->updateIdMethod($uuid, $formData['id_method']);
                return $this->redirect()->toRoute("root/cp_name_match_check", ['uuid' => $uuid]);
            }
        }

        $optionsdata = $this->config['opg_settings']['identity_methods'];
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $view->setVariable('options_data', $optionsdata);
        $view->setVariable('details_data', $detailsData);
        $view->setVariable('uuid', $uuid);

        return $view->setTemplate($templates['default']);
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

        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $lpaDetails = [];
        foreach ($detailsData['lpas'] as $lpa) {
            /**
             * @psalm-suppress ArgumentTypeCoercion
             */
            $lpasData = $this->siriusApiService->getLpaByUid($lpa, $this->request);
            /**
             * @psalm-suppress PossiblyNullArrayAccess
             */
            $lpaDetails[$lpa] = $lpasData['opg.poas.lpastore']['donor']['firstNames'] . " " .
                $lpasData['opg.poas.lpastore']['donor']['lastName'];
        }


        $view = new ViewModel();

        $view->setVariable('lpas', $detailsData['lpas']);
        $view->setVariable('lpa_count', count($detailsData['lpas']));
        $view->setVariable('details_data', $detailsData);
        $view->setVariable('lpa_details', $lpaDetails);
        $view->setVariable('case_uuid', $uuid);

        return $view->setTemplate('application/pages/cp/confirm_lpas');
    }

    public function addLpaAction(): ViewModel|Response
    {
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

            if ($formObject->get('lpa')) {
                $siriusCheck = $this->siriusApiService->getLpaByUid(
                    $formObject->get('lpa'),
                    $this->getRequest()
                );

                $processed = $this->lpaFormHelper->findLpa(
                    $uuid,
                    $formObject,
                    $form,
                    $siriusCheck,
                    $detailsData,
                );

                $view->setVariables(['lpa_response' => $processed->constructFormVariables()]);
                $view->setVariable('form', $processed->getForm());
                return $view->setTemplate('application/pages/cp/add_lpa');
            } else {
                $responseData = $this->opgApiService->updateCaseWithLpa($uuid, $formObject->get('add_lpa_number'));

                if ($responseData['result'] === 'Updated') {
                    return $this->redirect()->toRoute('root/cp_confirm_lpas', ['uuid' => $uuid]);
                }
            }
        }
        return $view->setTemplate('application/pages/cp/add_lpa');
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
        $routes = [
            'nin' => 'root/cp_national_insurance_number',
            'pn' => 'root/cp_passport_number',
            'dln' => 'root/cp_driving_licence_number',
            'po' => 'root/post_office_documents'
        ];
        $view = new ViewModel();
        $templates = [
            'default' => 'application/pages/cp/confirm_address_match',
        ];
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $view->setVariable('details_data', $detailsData);
        if (count($this->getRequest()->getPost())) {
            $params = $this->getRequest()->getPost();

            if ($params->get('confirm_alt') == '1') {
                return $this->redirect()->toRoute($routes[$detailsData['idMethod']], ['uuid' => $uuid]);
            }

            if ($params->get('chosenAddress') == 'yes') {
                return $this->redirect()->toRoute($routes[$detailsData['idMethod']], ['uuid' => $uuid]);
            } elseif ($params->get('chosenAddress') == 'no') {
                return $this->redirect()->toRoute('root/cp_enter_postcode', ['uuid' => $uuid]);
            }
        }

        return $view->setTemplate($templates['default']);
    }

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
            $formProcessorResponseDto = $this->formProcessorHelper->processNationalInsuranceNumberForm(
                $uuid,
                $this->getRequest()->getPost(),
                $form,
                $templates
            );
            foreach ($formProcessorResponseDto->getVariables() as $key => $variable) {
                $view->setVariable($key, $variable);
            }

            return $view->setTemplate($formProcessorResponseDto->getTemplate());
        }
        return $view->setTemplate($templates['default']);
    }

    public function drivingLicenceNumberAction(): ViewModel
    {
        $templates = [
            'default' => 'application/pages/driving_licence_number',
            'success' => 'application/pages/driving_licence_number_success',
            'fail' => 'application/pages/driving_licence_number_fail'
        ];
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $view->setVariable('uuid', $uuid);

        $form = (new AttributeBuilder())->createForm(DrivingLicenceNumber::class);
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('form', $form);

        if (count($this->getRequest()->getPost())) {
            $formProcessorResponseDto = $this->formProcessorHelper->processDrivingLicenceForm(
                $uuid,
                $this->getRequest()->getPost(),
                $form,
                $templates
            );

            foreach ($formProcessorResponseDto->getVariables() as $key => $variable) {
                $view->setVariable($key, $variable);
            }

            return $view->setTemplate($formProcessorResponseDto->getTemplate());
        }
        return $view->setTemplate($templates['default']);
    }

    public function passportNumberAction(): ViewModel
    {
        $templates = [
            'default' => 'application/pages/passport_number',
            'success' => 'application/pages/passport_number_success',
            'fail' => 'application/pages/passport_number_fail'
        ];
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $view->setVariable('uuid', $uuid);

        $form = (new AttributeBuilder())->createForm(PassportNumber::class);
        $dateSubForm = (new AttributeBuilder())->createForm(PassportDate::class);
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('form', $form);
        $view->setVariable('date_sub_form', $dateSubForm);
        $view->setVariable('details_open', false);

        if (count($this->getRequest()->getPost())) {
            $formData = $this->getRequest()->getPost();
            $data = $formData->toArray();
            $view->setVariable('passport', $data['passport']);

            if (array_key_exists('check_button', $formData->toArray())) {
                $formProcessorResponseDto = $this->formProcessorHelper->processPassportDateForm(
                    $uuid,
                    $this->getRequest()->getPost(),
                    $dateSubForm,
                    $templates
                );
            } else {
                $view->setVariable('passport_indate', ucwords($data['inDate']));
                $formProcessorResponseDto = $this->formProcessorHelper->processPassportForm(
                    $uuid,
                    $this->getRequest()->getPost(),
                    $form,
                    $templates
                );
            }
            foreach ($formProcessorResponseDto->getVariables() as $key => $variable) {
                $view->setVariable($key, $variable);
            }
            return $view->setTemplate($formProcessorResponseDto->getTemplate());
        }
        return $view->setTemplate($templates['default']);
    }

    public function identityCheckPassedAction(): ViewModel
    {
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $lpaDetails = [];
        foreach ($detailsData['lpas'] as $lpa) {
            /**
             * @psalm-suppress ArgumentTypeCoercion
             */
            $lpasData = $this->siriusApiService->getLpaByUid($lpa, $this->request);
            /**
             * @psalm-suppress PossiblyNullArrayAccess
             */
            $lpaDetails[$lpa] = $lpasData['opg.poas.lpastore']['donor']['firstNames'] . " " .
                $lpasData['opg.poas.lpastore']['donor']['lastName'];
        }

        $view = new ViewModel();

        $view->setVariable('lpas_data', $lpaDetails);
        $view->setVariable('details_data', $detailsData);

        return $view->setTemplate('application/pages/identity_check_passed');
    }

    public function identityCheckFailedAction(): ViewModel
    {
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $lpaDetails = [];
        foreach ($detailsData['lpas'] as $lpa) {
            /**
             * @psalm-suppress ArgumentTypeCoercion
             */
            $lpasData = $this->siriusApiService->getLpaByUid($lpa, $this->request);
            /**
             * @psalm-suppress PossiblyNullArrayAccess
             */
            $lpaDetails[$lpa] = $lpasData['opg.poas.lpastore']['donor']['firstNames'] . " " .
                $lpasData['opg.poas.lpastore']['donor']['lastName'];
        }

        $view = new ViewModel();

        $view->setVariable('lpas_data', $lpaDetails);
        $view->setVariable('details_data', $detailsData);

        return $view->setTemplate('application/pages/identity_check_failed');
    }

    public function enterPostcodeAction(): ViewModel
    {
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $view = new ViewModel();
        $view->setVariable('details_data', $detailsData);
        $form = (new AttributeBuilder())->createForm(Postcode::class);
        $view->setVariable('form', $form);

        if (count($this->getRequest()->getPost())) {
            $params = $this->getRequest()->getPost();
            $form->setData($params);

            if ($form->isValid()) {
                /**
                 * @psalm-suppress InvalidMethodCall
                 */
                $response = $this->siriusApiService->searchAddressesByPostcode(
                    $params->get('postcode'),
                    $this->getRequest()
                );
                $processedAddresses = [];
                foreach ($response as $foundAddress) {
                    $processedAddresses[] = $this->addressProcessorHelper->processAddress(
                        $foundAddress,
                        'siriusAddressType'
                    );
                }
                $addressStrings = $this->addressProcessorHelper->stringifyAddresses($processedAddresses);
                $view->setVariable('addresses', $addressStrings);
                $view->setVariable('addresses_count', count($addressStrings));
                return $view->setTemplate('application/pages/cp/select_address');
            }
        }

        return $view->setTemplate('application/pages/cp/enter_address');
    }

    public function selectAddressAction(): ViewModel|Response
    {
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $view = new ViewModel();
        $view->setVariable('details_data', $detailsData);

        if (count($this->getRequest()->getPost())) {
            $params = $this->getRequest()->getPost();

            $structuredAddress = json_decode($params->get('address_json'), true);

            $response = $this->opgApiService->addSelectedAltAddress($uuid, $structuredAddress);

            if ($response) {
                return $this->redirect()->toRoute('root/cp_enter_address_manual', ['uuid' => $uuid]);
            }
        }
        return $view->setTemplate('application/pages/cp/select_address');
    }

    public function enterAddressManualAction(): ViewModel|Response
    {
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $form = (new AttributeBuilder())->createForm(CpAltAddress::class);
        $form->setData($detailsData['alternateAddress']);

        if (count($this->getRequest()->getPost())) {
            $params = $this->getRequest()->getPost();

            $form->setData($params);

            if ($form->isValid()) {
                /**
                 * @psalm-suppress InvalidMethodCall
                 */
                $response = $this->opgApiService->addSelectedAltAddress($uuid, $params->toArray());
                if ($response) {
                    return $this->redirect()->toRoute('root/cp_confirm_address', ['uuid' => $uuid]);
                }
            }
        }

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('form', $form);

        return $view->setTemplate('application/pages/cp/enter_address_manual');
    }

    public function removeLpaAction(): Response
    {
        $uuid = $this->params()->fromRoute("uuid");
        $lpa = $this->params()->fromRoute("lpa");

        $this->opgApiService->updateCaseWithLpa($uuid, $lpa, true);

        return $this->redirect()->toRoute("root/cp_confirm_lpas", ['uuid' => $uuid]);
    }
}

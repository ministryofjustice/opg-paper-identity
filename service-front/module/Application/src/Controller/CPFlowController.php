<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Enums\LpaTypes;
use Application\Exceptions\PostcodeInvalidException;
use Application\Forms\AddressJson;
use Application\Forms\BirthDate;
use Application\Forms\ConfirmAddress;
use Application\Forms\AddressInput;
use Application\Forms\DrivingLicenceNumber;
use Application\Forms\IdMethod;
use Application\Forms\LpaReferenceNumber;
use Application\Forms\NationalInsuranceNumber;
use Application\Forms\PassportDate;
use Application\Forms\PassportDateCp;
use Application\Forms\PassportNumber;
use Application\Forms\Postcode;
use Application\Helpers\AddressProcessorHelper;
use Application\Helpers\DateProcessorHelper;
use Application\Helpers\FormProcessorHelper;
use Application\Helpers\LpaFormHelper;
use Application\Helpers\SiriusDataProcessorHelper;
use Application\Services\SiriusApiService;
use Application\Enums\IdMethod as IdMethodEnum;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Psr\Log\LoggerInterface;

class CPFlowController extends AbstractActionController
{
    use FormBuilder;

    protected $plugins;
    public const ERROR_POSTCODE_NOT_FOUND = 'The entered postcode could not be found. Please try a valid postcode.';

    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly FormProcessorHelper $formProcessorHelper,
        private readonly SiriusApiService $siriusApiService,
        private readonly AddressProcessorHelper $addressProcessorHelper,
        private readonly LpaFormHelper $lpaFormHelper,
        private readonly array $config,
        private readonly string $siriusPublicUrl,
        private readonly SiriusDataProcessorHelper $siriusDataProcessorHelper,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function howWillCpConfirmAction(): ViewModel|Response
    {
        $templates = [
            'default' => 'application/pages/cp/how_will_the_cp_confirm',
        ];
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $dateSubForm = $this->createForm(PassportDateCp::class);
        $form = $this->createForm(IdMethod::class);
        $view->setVariable('date_sub_form', $dateSubForm);

        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $serviceAvailability = $this->opgApiService->getServiceAvailability($uuid);

        $identityDocs = [];
        foreach ($this->config['opg_settings']['identity_documents'] as $key => $value) {
            if ($serviceAvailability['data'][$key] === true) {
                $identityDocs[$key] = $value;
            }
        }

        $optionsData = $identityDocs;
        $view->setVariable('service_availability', $serviceAvailability);
        $view->setVariable('form', $form);

        if (count($this->getRequest()->getPost())) {
            $formData = $this->getRequest()->getPost()->toArray();
            if (array_key_exists('check_button', $formData)) {
                $formProcessorResponseDto = $this->formProcessorHelper->processPassportDateForm(
                    $uuid,
                    $this->getRequest()->getPost(),
                    $dateSubForm,
                    $templates
                );
                $view->setVariables($formProcessorResponseDto->getVariables());
            } else {
                if ($form->isValid()) {
                    if ($formData['id_method'] == IdMethodEnum::PostOffice->value) {
                        $data = [
                            'id_route' => IdMethodEnum::PostOffice->value,
                        ];
                        $this->opgApiService->updateIdMethodWithCountry(
                            $uuid,
                            $data
                        );
                        return $this->redirect()->toRoute("root/post_office_documents", ['uuid' => $uuid]);
                    } else {
                        $data = [
                            'id_route' => 'TELEPHONE',
                            'id_country' => \Application\PostOffice\Country::GBR->value,
                            'id_method' => $formData['id_method']
                        ];
                        $this->opgApiService->updateIdMethodWithCountry(
                            $uuid,
                            $data
                        );
                        return $this->redirect()->toRoute("root/cp_name_match_check", ['uuid' => $uuid]);
                    }
                }
            }
        }

        $view->setVariable('options_data', $optionsData);
        $view->setVariable('details_data', $detailsData);
        $view->setVariable('uuid', $uuid);

        return $view->setTemplate($templates['default']);
    }

    public function nameMatchCheckAction(): ViewModel
    {
        $uuid = $this->params()->fromRoute("uuid");

        try {
            $this->siriusDataProcessorHelper->updatePaperIdCaseFromSirius($uuid, $this->getRequest());
        } catch (\Exception $e) {
            $this->logger->error('Unable to update paper id case from Sirius', ['exception' => $e]);
        }

        $optionsdata = $this->config['opg_settings']['identity_documents'];
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $view = new ViewModel();

        $siriusEditUrl = $this->siriusPublicUrl . '/lpa/frontend/lpa/' . $detailsData["lpas"][0];

        $view->setVariables([
            'options_data' => $optionsdata,
            'details_data' => $detailsData,
            'sirius_edit_url' => $siriusEditUrl
        ]);

        return $view->setTemplate('application/pages/cp/cp_id_check');
    }

    public function confirmLpasAction(): ViewModel
    {
        $uuid = $this->params()->fromRoute("uuid");

        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $lpaDetails = [];
        foreach ($detailsData['lpas'] as $lpa) {
            $lpasData = $this->siriusApiService->getLpaByUid($lpa, $this->request);
            /**
             * @psalm-suppress PossiblyNullArrayAccess
             */
            $name = $lpasData['opg.poas.lpastore']['donor']['firstNames'] . " " .
                $lpasData['opg.poas.lpastore']['donor']['lastName'];

            /**
             * @psalm-suppress PossiblyNullArrayAccess
             * @psalm-suppress PossiblyNullArgument
             */
            $type = LpaTypes::fromName($lpasData['opg.poas.lpastore']['lpaType']);

            $lpaDetails[$lpa] = [
                'name' => $name,
                'type' => $type,
            ];
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
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $form = $this->createForm(LpaReferenceNumber::class);

        $view = new ViewModel();
        $view->setVariable('details_data', $detailsData);
        $view->setVariable('form', $form);
        $view->setVariable('case_uuid', $uuid);

        if (count($this->getRequest()->getPost())) {
            if (! $form->isValid()) {
                $form->setMessages([
                    'lpa' => [
                        "Not a valid LPA number. Enter an LPA number to continue.",
                    ],
                ]);

                return $view->setTemplate('application/pages/cp/add_lpa');
            }

            $formArray = $this->formToArray($form);
            if ($formArray['lpa']) {
                $siriusCheck = $this->siriusApiService->getLpaByUid(
                    /**
                     * @psalm-suppress InvalidMethodCall
                     */
                    $formArray['lpa'],
                    $this->getRequest()
                );

                $processed = $this->lpaFormHelper->findLpa(
                    $uuid,
                    $form,
                    $siriusCheck,
                    $detailsData,
                );

                $view->setVariables(['lpa_response' => $processed->constructFormVariables()]);
                $view->setVariable('form', $processed->getForm());

                return $view->setTemplate('application/pages/cp/add_lpa');
            } else {
                $this->opgApiService->updateCaseWithLpa($uuid, $this->getRequest()->getPost()->get('add_lpa_number'));

                return $this->redirect()->toRoute('root/cp_confirm_lpas', ['uuid' => $uuid]);
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
        $form = $this->createForm(BirthDate::class);


        if (count($this->getRequest()->getPost())) {
            $params = $this->getRequest()->getPost();
            $dateOfBirth = $this->formProcessorHelper->processDateForm($params->toArray());
            $params->set('date', $dateOfBirth);
            $form->setData($params);

            if ($form->isValid()) {
                try {
                    $this->opgApiService->updateCaseSetDob($uuid, $dateOfBirth);

                    return $this->redirect()->toRoute('root/cp_confirm_address', ['uuid' => $uuid]);
                } catch (\Exception $exception) {
                    $form->setMessages(["There was an error saving the data"]);
                }
            }
            $view->setVariable('form', $form);
        }
        /**
         * @psalm-suppress TooManyArguments
         */
        $messages = $form->getMessages("date");
        if (isset($messages["date_under_18"])) {
            $view->setVariable("date_error", $messages["date_under_18"]);
        } elseif (! empty($messages)) {
            $view->setVariable("date_problem", $messages);
        }

        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $view->setVariable('details_data', $detailsData);

        return $view->setTemplate($templates['default']);
    }

    public function confirmAddressAction(): ViewModel|Response
    {
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $form = $this->createForm(ConfirmAddress::class);

        $routes = [
            'NATIONAL_INSURANCE_NUMBER' => 'root/cp_national_insurance_number',
            'DRIVING_LICENCE' => 'root/cp_driving_licence_number',
            'PASSPORT' => 'root/cp_passport_number',
        ];

        /**
         * @psalm-suppress PossiblyUndefinedArrayOffset
         */
        if ($detailsData['idMethodIncludingNation']['id_route'] != 'TELEPHONE') {
            $nextRoute = 'root/find_post_office_branch';
        } else {
            $nextRoute = $routes[$detailsData['idMethodIncludingNation']['id_method']];
        }

        $view->setVariables([
            'details_data' => $detailsData,
            'form' => $form,
        ]);

        if ($this->getRequest()->isPost()) {
            $params = $this->getRequest()->getPost();

            if ($params['confirm_alt'] == 'confirmed') {
                return $this->redirect()->toRoute($nextRoute, ['uuid' => $uuid]);
            }

            if ($form->isValid()) {
                $formArray = $this->formToArray($form);

                if ($formArray['chosenAddress'] == 'yes') {
                    return $this->redirect()->toRoute($nextRoute, ['uuid' => $uuid]);
                } elseif ($formArray['chosenAddress'] == 'no') {
                    return $this->redirect()->toRoute('root/cp_enter_postcode', ['uuid' => $uuid]);
                }
            }
        }

        return $view->setTemplate('application/pages/cp/confirm_address_match');
    }

    public function nationalInsuranceNumberAction(): ViewModel
    {
        $templates = $this->config['opg_settings']['template_options']['NATIONAL_INSURANCE_NUMBER'];
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $view->setVariable('uuid', $uuid);

        $form = $this->createForm(NationalInsuranceNumber::class);
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $serviceAvailability = $this->opgApiService->getServiceAvailability($uuid);
        $view->setVariable('service_availability', $serviceAvailability);

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('formattedDob', DateProcessorHelper::formatDate($detailsData['dob']));
        $view->setVariable('form', $form);

        if ($this->getRequest()->isPost() && $form->isValid()) {
            $formProcessorResponseDto = $this->formProcessorHelper->processNationalInsuranceNumberForm(
                $uuid,
                $form,
                $templates
            );
            $view->setVariables($formProcessorResponseDto->getVariables());

            if ($formProcessorResponseDto->getVariables()['validity'] === 'PASS') {
                $fraudCheck = $this->opgApiService->requestFraudCheck($uuid);
                $template = $this->formProcessorHelper->processTemplate($fraudCheck, $templates);
            } else {
                $template = $templates['fail'];
            }

            $this->opgApiService->updateCaseSetDocumentComplete($uuid, IdMethodEnum::NationalInsuranceNumber->value);

            return $view->setTemplate($template);
        }

        return $view->setTemplate($templates['default']);
    }

    public function drivingLicenceNumberAction(): ViewModel
    {
        $templates = $this->config['opg_settings']['template_options']['DRIVING_LICENCE'];
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $view->setVariable('uuid', $uuid);

        $form = $this->createForm(DrivingLicenceNumber::class);
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $serviceAvailability = $this->opgApiService->getServiceAvailability($uuid);
        $view->setVariable('service_availability', $serviceAvailability);

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('formattedDob', DateProcessorHelper::formatDate($detailsData['dob']));
        $view->setVariable('form', $form);

        if ($this->getRequest()->isPost() && $form->isValid()) {
            $formProcessorResponseDto = $this->formProcessorHelper->processDrivingLicenceForm(
                $uuid,
                $form,
                $templates
            );
            $view->setVariables($formProcessorResponseDto->getVariables());
            if ($formProcessorResponseDto->getVariables()['validity'] === 'PASS') {
                $fraudCheck = $this->opgApiService->requestFraudCheck($uuid);
                $template = $this->formProcessorHelper->processTemplate($fraudCheck, $templates);
            } else {
                $template = $templates['fail'];
            }
            $this->opgApiService->updateCaseSetDocumentComplete($uuid, IdMethodEnum::DrivingLicenseNumber->value);

            return $view->setTemplate($template);
        }

        return $view->setTemplate($templates['default']);
    }

    public function passportNumberAction(): ViewModel
    {
        $templates = $this->config['opg_settings']['template_options']['PASSPORT'];
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $view->setVariable('uuid', $uuid);

        $form = $this->createForm(PassportNumber::class);
        $dateSubForm = $this->createForm(PassportDate::class);
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $serviceAvailability = $this->opgApiService->getServiceAvailability($uuid);
        $view->setVariable('service_availability', $serviceAvailability);

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('formattedDob', DateProcessorHelper::formatDate($detailsData['dob']));
        $view->setVariable('form', $form);
        $view->setVariable('date_sub_form', $dateSubForm);
        $view->setVariable('details_open', false);

        if ($this->getRequest()->isPost() && $form->isValid()) {
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
                $view->setVariable(
                    'passport_indate',
                    array_key_exists('inDate', $data) ?
                        ucwords($data['inDate']) :
                        'no'
                );
                $formProcessorResponseDto = $this->formProcessorHelper->processPassportForm(
                    $uuid,
                    $form,
                    $templates
                );
            }

            $view->setVariables($formProcessorResponseDto->getVariables());
            if ($formProcessorResponseDto->getVariables()['validity'] === 'PASS') {
                $fraudCheck = $this->opgApiService->requestFraudCheck($uuid);
                $template = $this->formProcessorHelper->processTemplate($fraudCheck, $templates);
            } else {
                $template = $templates['fail'];
            }
            $this->opgApiService->updateCaseSetDocumentComplete($uuid, IdMethodEnum::PassportNumber->value);

            return $view->setTemplate($template);
        }

        return $view->setTemplate($templates['default']);
    }

    public function identityCheckPassedAction(): ViewModel
    {
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid, true);
        $view = new ViewModel();

        $view->setVariable('details_data', $detailsData);
        $view->setVariable(
            'sirius_url',
            $this->siriusPublicUrl . '/lpa/frontend/lpa/' . $detailsData["lpas"][0]
        );
        return $view->setTemplate('application/pages/cp/identity_check_passed');
    }

    public function identityCheckFailedAction(): ViewModel
    {
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid, true);
        $lpaDetails = [];
        foreach ($detailsData['lpas'] as $lpa) {
            $lpasData = $this->siriusApiService->getLpaByUid($lpa, $this->request);
            /**
             * @psalm-suppress PossiblyNullArrayAccess
             */
            $lpaDetails[$lpa] = $lpasData['opg.poas.lpastore']['donor']['firstNames'] . " " .
                /**
                 * @psalm-suppress PossiblyNullArrayAccess
                 */
                $lpasData['opg.poas.lpastore']['donor']['lastName'];
        }

        $view = new ViewModel();

        $view->setVariable('lpas_data', $lpaDetails);
        $view->setVariable('details_data', $detailsData);

        return $view->setTemplate('application/pages/identity_check_failed');
    }

    public function enterPostcodeAction(): ViewModel|Response
    {
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $view = new ViewModel();
        $view->setVariable('details_data', $detailsData);
        $form = $this->createForm(Postcode::class);
        $view->setVariable('form', $form);

        if ($this->getRequest()->isPost() && $form->isValid()) {
            $postcode = $this->formToArray($form)['postcode'];

            try {
                $response = $this->siriusApiService->searchAddressesByPostcode($postcode, $this->getRequest());

                if (empty($response)) {
                    $form->setMessages([
                        'postcode' => [$this->addressProcessorHelper::ERROR_POSTCODE_NOT_FOUND],
                    ]);
                } else {
                    return $this->redirect()->toRoute(
                        'root/cp_select_address',
                        [
                            'uuid' => $uuid,
                            'postcode' => $postcode,
                        ]
                    );
                }
            } catch (PostcodeInvalidException $e) {
                $form->setMessages([
                    'postcode' => [$this->addressProcessorHelper::ERROR_POSTCODE_NOT_FOUND],
                ]);
            }
        }

        return $view->setTemplate('application/pages/cp/enter_address');
    }

    public function selectAddressAction(): ViewModel|Response
    {
        $uuid = $this->params()->fromRoute("uuid");
        $postcode = $this->params()->fromRoute("postcode");

        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $form = $this->createForm(AddressJson::class);

        $view = new ViewModel();
        $view->setVariables([
            'details_data' => $detailsData,
            'form' => $form,
        ]);

        $response = $this->siriusApiService->searchAddressesByPostcode(
            $postcode,
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

        if ($this->getRequest()->isPost() && $form->isValid()) {
            $formData = $this->formToArray($form);

            $structuredAddress = json_decode($formData['address_json'], true);
            $existingAddress = $detailsData['address'];

            $this->opgApiService->updateCaseAddress($uuid, $structuredAddress);

            if (! isset($detailsData['professionalAddress'])) {
                $this->opgApiService->updateCaseProfessionalAddress($uuid, $existingAddress);
            }

            return $this->redirect()->toRoute('root/cp_enter_address_manual', ['uuid' => $uuid]);
        }

        return $view->setTemplate('application/pages/cp/select_address');
    }

    public function enterAddressManualAction(): ViewModel|Response
    {
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $form = $this->createForm(AddressInput::class);
        $form->setData($detailsData['address']);

        $countryList = $this->siriusApiService->getCountryList($this->getRequest());
        $view->setVariable('country_list', $countryList);

        if ($this->getRequest()->isPost()) {
            $params = $this->getRequest()->getPost();

            $form->setData($params);

            if ($form->isValid()) {
                $this->opgApiService->updateCaseAddress($uuid, $this->formToArray($form));

                $existingAddress = $detailsData['address'];

                if (! isset($detailsData['professionalAddress'])) {
                    $this->opgApiService->updateCaseProfessionalAddress($uuid, $existingAddress);
                }

                return $this->redirect()->toRoute('root/cp_confirm_address', ['uuid' => $uuid]);
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

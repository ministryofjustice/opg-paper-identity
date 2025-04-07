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
use Application\Forms\LpaReferenceNumber;
use Application\Forms\LpaReferenceNumberAdd;
use Application\Forms\Postcode;
use Application\Helpers\AddressProcessorHelper;
use Application\Helpers\FormProcessorHelper;
use Application\Helpers\LpaFormHelper;
use Application\Helpers\SiriusDataProcessorHelper;
use Application\Services\SiriusApiService;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Psr\Log\LoggerInterface;
use Application\Controller\Trait\DobOver100WarningTrait;
use Application\Enums\DocumentType;
use Application\Enums\IdRoute;

class CPFlowController extends AbstractActionController
{
    use FormBuilder;
    use DobOver100WarningTrait;

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
        private readonly LoggerInterface $logger
    ) {
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
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");

        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $view->setVariable(
            'lpa_details',
            $this->siriusDataProcessorHelper->createLpaDetailsArray($detailsData, $this->request)
        );

        $view->setVariable('lpas', $detailsData['lpas']);
        $view->setVariable('lpa_count', count($detailsData['lpas']));
        $view->setVariable('details_data', $detailsData);
        $view->setVariable('case_uuid', $uuid);

        return $view->setTemplate('application/pages/cp/confirm_lpas');
    }

    public function addLpaAction(): ViewModel|Response
    {
        $template = 'application/pages/cp/add_lpa';
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $form = $this->createForm(LpaReferenceNumber::class);
        $view = new ViewModel();
        $view->setVariable('details_data', $detailsData);

        if ($this->getRequest()->isPost()) {
            if ($this->getRequest()->getPost()->get('add_lpa_number')) {
                $form = $this->createForm(LpaReferenceNumberAdd::class);
            }

            $view->setVariables([
                'case_uuid' => $uuid,
                'form' => $form
            ]);

            if ($this->getRequest()->isPost()) {
                if (! $form->isValid()) {
                    $form->setMessages([
                        'lpa' => [
                            "Not a valid LPA number. Enter an LPA number to continue.",
                        ],
                    ]);

                    return $view->setTemplate($template);
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

                    $view->setVariables([
                        'lpa_response' => $processed->constructFormVariables(),
                        'form' => $processed->getForm()
                    ]);

                    return $view->setTemplate($template);
                } else {
                    $this->opgApiService->updateCaseWithLpa(
                        $uuid,
                        $this->getRequest()->getPost()->get('add_lpa_number')
                    );

                    return $this->redirect()->toRoute('root/cp_confirm_lpas', ['uuid' => $uuid]);
                }
            }
        }
        return $view->setTemplate($template);
    }

    public function confirmDobAction(): ViewModel|Response
    {
        $view = new ViewModel();
        $templates = [
            'default' => 'application/pages/confirm_dob',
        ];
        $uuid = $this->params()->fromRoute("uuid");
        $form = $this->createForm(BirthDate::class);

        if (count($this->getRequest()->getPost())) {
            $params = $this->getRequest()->getPost();
            $dateOfBirth = $this->formProcessorHelper->processDateForm($params->toArray());
            $params->set('date', $dateOfBirth);
            $form->setData($params);

            if ($form->isValid()) {
                $proceed = $this->handleDobOver100Warning(
                    $dateOfBirth,
                    $this->getRequest(),
                    $view,
                    function () use ($uuid, $dateOfBirth, $form) {
                        try {
                            $this->opgApiService->updateCaseSetDob($uuid, $dateOfBirth);
                        } catch (\Exception $exception) {
                            $form->setMessages(["There was an error saving the data"]);
                        }
                    }
                );

                if ($proceed) {
                    return $this->redirect()->toRoute('root/cp_confirm_address', ['uuid' => $uuid]);
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

        if (! is_null($detailsData['dob'])) {
            $dob = \DateTime::createFromFormat('Y-m-d', $detailsData['dob']);
            $form->setData([
                'dob_day' => date_format($dob, 'd'),
                'dob_month' => date_format($dob, 'm'),
                'dob_year' => date_format($dob, 'Y')
            ]);
        }

        $view->setVariables([
            'form' => $form,
            'details_data' => $detailsData,
            'include_fraud_id_check_info' => true,
            'warning_message' => 'By continuing, you confirm that the certificate provider is more than 100 years old. 
            If not, please change the date.',
        ]);
        return $view->setTemplate($templates['default']);
    }

    public function confirmAddressAction(): ViewModel|Response
    {
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);


        $form = $this->createForm(ConfirmAddress::class);

        $routes = [
            DocumentType::NationalInsuranceNumber->value => 'root/national_insurance_number',
            DocumentType::DrivingLicence->value  => 'root/driving_licence_number',
            DocumentType::Passport->value  => 'root/passport_number',
        ];

        /**
         * @psalm-suppress PossiblyUndefinedArrayOffset
         */
        if ($detailsData['idMethod']['idRoute'] != IdRoute::KBV->value) {
            $nextRoute = 'root/find_post_office_branch';
        } else {
            $nextRoute = $routes[$detailsData['idMethod']['docType']];
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

    public function identityCheckPassedAction(): ViewModel
    {
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $view = new ViewModel();

        $view->setVariable('details_data', $detailsData);
        $view->setVariable(
            'sirius_url',
            $this->siriusPublicUrl . '/lpa/frontend/lpa/' . $detailsData["lpas"][0]
        );
        return $view->setTemplate('application/pages/cp/identity_check_passed');
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

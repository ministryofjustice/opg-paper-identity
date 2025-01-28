<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Enums\LpaTypes;
use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Exceptions\PostcodeInvalidException;
use Application\Forms\AddressInput;
use Application\Forms\VoucherBirthDate;
use Application\Forms\ConfirmVouching;
use Application\Forms\VoucherName;
use Application\Forms\AddDonor;
use Application\Model\Entity\CaseData;
use Application\Services\SiriusApiService;
use Application\Helpers\AddressProcessorHelper;
use Application\Helpers\FormProcessorHelper;
use Application\Helpers\AddDonorFormHelper;
use Application\Helpers\VoucherMatchLpaActorHelper;
use Application\Forms\IdMethod;
use Application\Forms\Postcode;
use Application\Forms\AddressJson;
use Application\Forms\PassportDateCp;
use Laminas\Form\FormInterface;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Application\Enums\IdMethod as IdMethodEnum;
use DateTime;

class VouchingFlowController extends AbstractActionController
{
    use FormBuilder;

    protected $plugins;
    private string $uuid;

    public array $routes = [
        "confirm_donors" => "root/voucher_confirm_donors",
    ];

    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly SiriusApiService $siriusApiService,
        private readonly FormProcessorHelper $formProcessorHelper,
        private readonly VoucherMatchLpaActorHelper $voucherMatchLpaActorHelper,
        private readonly AddressProcessorHelper $addressProcessorHelper,
        private readonly AddDonorFormHelper $addDonorFormHelper,
        private readonly array $config
    ) {
    }

    public function confirmVouchingAction(): ViewModel|Response
    {
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $form = $this->createForm(ConfirmVouching::class);

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('form', $form);

        if ($this->getRequest()->isPost()) {
            $formData = $this->getRequest()->getPost();

            if (isset($formData['tryDifferent'])) {
                // start the donor journey instead
                $baseStartUrl = $this->url()->fromRoute('root/start');
                return $this->redirect()->toUrl(
                    $baseStartUrl . "?personType=donor&lpas[]=" . implode("&lpas[]=", $detailsData['lpas'])
                );
            }

            if ($form->isValid()) {
                return $this->redirect()->toRoute("root/vouching_how_will_you_confirm", ['uuid' => $uuid]);
            }
        }
        return $view->setTemplate('application/pages/vouching/confirm_vouching');
    }

    public function howWillYouConfirmAction(): ViewModel|Response
    {
        $templates = ['default' => 'application/pages/vouching/how_will_you_confirm'];
        $view = new ViewModel();
        $this->uuid = $this->params()->fromRoute("uuid");
        $dateSubForm = $this->createForm(PassportDateCp::class);
        $form = $this->createForm(IdMethod::class);
        $view->setVariable('date_sub_form', $dateSubForm);

        $detailsData = $this->opgApiService->getDetailsData($this->uuid);

        $serviceAvailability = $this->opgApiService->getServiceAvailability($this->uuid);

        $identityDocs = [];
        foreach ($this->config['opg_settings']['identity_documents'] as $key => $value) {
            $data = $serviceAvailability['data'] ?? [];
            if (isset($data[$key]) && $data[$key] === true) {
                $identityDocs[$key] = $value;
            }
        }

        $optionsData = $identityDocs;
        $view->setVariable('service_availability', $serviceAvailability);
        $view->setVariable('form', $form);

        if (count($this->getRequest()->getPost())) {
            $formData = $this->getRequest()->getPost()->toArray();
            if (array_key_exists('check_button', $formData)) {
                $variables = $this->handlePassportDateCheckFormSubmission($dateSubForm, $templates);
                $view->setVariables($variables);
            } else {
                $response = $this->handleIdMethodFormSubmission($form, $formData);
                if ($response) {
                    return $response;
                }
            }
        }

        $view->setVariable('options_data', $optionsData);
        $view->setVariable('details_data', $detailsData);
        $view->setVariable('uuid', $this->uuid);

        return $view->setTemplate($templates['default']);
    }

    /**
     * @param FormInterface $idMethodForm
     * @param array<string, mixed> $formData
     * @return Response|null
     */
    private function handleIdMethodFormSubmission(FormInterface $idMethodForm, array $formData): Response|null
    {
        if (! $idMethodForm->isValid()) {
            return null;
        }

        if ($formData['id_method'] == IdMethodEnum::PostOffice->value) {
            $data = [
                'id_route' => IdMethodEnum::PostOffice->value,
            ];
            $this->opgApiService->updateIdMethodWithCountry(
                $this->uuid,
                $data
            );
            return $this->redirect()->toRoute("root/post_office_documents", ['uuid' => $this->uuid]);
        }

        $data = [
            'id_route' => 'TELEPHONE',
            'id_country' => \Application\PostOffice\Country::GBR->value,
            'id_method' => $formData['id_method']
        ];
        $this->opgApiService->updateIdMethodWithCountry(
            $this->uuid,
            $data
        );

        return $this->redirect()->toRoute("root/voucher_name", ['uuid' => $this->uuid]);
    }

    /**
     * @param FormInterface $dateSubForm
     * @param array<string, mixed> $templates
     * @return array<string, mixed>
     */
    private function handlePassportDateCheckFormSubmission(FormInterface $dateSubForm, array $templates): array
    {
        $formProcessorResponseDto = $this->formProcessorHelper->processPassportDateForm(
            $this->uuid,
            $this->getRequest()->getPost(),
            $dateSubForm,
            $templates
        );
        return $formProcessorResponseDto->getVariables();
    }

    public function voucherNameAction(): ViewModel|Response
    {
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $form = $this->createForm(VoucherName::class);
        $form->setData([
            "firstName" => $detailsData["firstName"],
            "lastName" => $detailsData["lastName"]
        ]);

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('form', $form);

        if ($this->getRequest()->isPost()) {
            $formData = $this->getRequest()->getPost();
            $form->setData($formData->toArray());

            if ($form->isValid()) {
                $match = false;
                foreach ($detailsData['lpas'] as $lpa) {
                    $lpasData = $this->siriusApiService->getLpaByUid($lpa, $this->getRequest());
                    $match = $this->voucherMatchLpaActorHelper->checkMatch(
                        $lpasData,
                        $formData["firstName"],
                        $formData["lastName"],
                    );
                    // we raise the warning if there are any matches so stop once we've found one
                    if ($match) {
                        break;
                    }
                }
                if ($match && ! isset($formData["continue-after-warning"])) {
                    $view->setVariable('match', $match);
                    $view->setVariable('matched_name', $formData["firstName"] . ' ' . $formData["lastName"]);
                } else {
                    try {
                        $this->opgApiService->updateCaseSetName($uuid, $formData["firstName"], $formData["lastName"]);
                        return $this->redirect()->toRoute("root/voucher_dob", ['uuid' => $uuid]);
                    } catch (\Exception $exception) {
                        $form->setMessages(["There was an error saving the data"]);
                    }
                }
            }
        }
        return $view->setTemplate('application/pages/vouching/what_is_the_voucher_name');
    }

    private function checkMatchesForLpas(array $detailsData, string $dateOfBirth): bool | array
    {
        $match = false;
        foreach ($detailsData['lpas'] as $lpa) {
            $lpasData = $this->siriusApiService->getLpaByUid($lpa, $this->getRequest());
            $match = $this->voucherMatchLpaActorHelper->checkMatch(
                $lpasData,
                $detailsData["firstName"],
                $detailsData["lastName"],
                $dateOfBirth,
            );
            // we raise the warning if there are any matches so stop once we've found one
            if ($match) {
                break;
            }
        }
        return $match;
    }

    public function voucherDobAction(): ViewModel|Response
    {
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $form = $this->createForm(VoucherBirthDate::class);
        /**
         * @psalm-suppress RedundantConditionGivenDocblockType
         */
        if (! is_null($detailsData['dob'])) {
            $dob = DateTime::createFromFormat('Y-m-d', $detailsData['dob']);
            $form->setData([
                'dob_day' => date_format($dob, 'd'),
                'dob_month' => date_format($dob, 'm'),
                'dob_year' => date_format($dob, 'Y')
            ]);
        }
        $view->setVariable('details_data', $detailsData);
        $view->setVariable('form', $form);

        if ($this->getRequest()->isPost()) {
            $formData = $this->getRequest()->getPost();
            $dateOfBirth = $this->formProcessorHelper->processDateForm($formData->toArray());

            $formData->set('date', $dateOfBirth);
            $form->setData($formData);
            $view->setVariable('form', $form);

            if ($form->isValid()) {
                $match = $this->checkMatchesForLpas($detailsData, $dateOfBirth);
                if ($match !== false) {
                    $view->setVariable('match', $match);
                } else {
                    $birthDate = strtotime($dateOfBirth);
                    $maxBirthDate = strtotime('-100 years', time());

                    if ($birthDate < $maxBirthDate) {
                        $warningAccepted = $this->getRequest()->getPost('dob_warning_100_accepted') ?? false;

                        if ($warningAccepted != false) {
                            try {
                                $this->opgApiService->updateCaseSetDob($uuid, $dateOfBirth);
                                if (isset($detailsData["address"])) {
                                    return $this->redirect()
                                        ->toRoute("root/voucher_enter_address_manual", ['uuid' => $uuid]);
                                } else {
                                    return $this->redirect()
                                        ->toRoute("root/voucher_enter_postcode", ['uuid' => $uuid]);
                                }
                            } catch (\Exception $exception) {
                                $form->setMessages(["There was an error saving the data"]);
                            }
                        } else {
                            // Show the warning if the user hasn't confirmed yet
                            $form->setMessages([
                                'date' => ['By continuing, you confirm that the certificate provider
                                 is more than 100 years old.']
                            ]);

                            // Include the hidden field that represents the user confirming CP is over 100 years old
                            $view->setVariable('displaying_dob_100_warning', true);
                        }
                    } else {
                        try {
                            $this->opgApiService->updateCaseSetDob($uuid, $dateOfBirth);
                            if (isset($detailsData["address"])) {
                                return $this->redirect()
                                    ->toRoute("root/voucher_enter_address_manual", ['uuid' => $uuid]);
                            } else {
                                return $this->redirect()
                                    ->toRoute("root/voucher_enter_postcode", ['uuid' => $uuid]);
                            }
                        } catch (\Exception $exception) {
                            $form->setMessages(["There was an error saving the data"]);
                        }
                    }
                }
            }
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
        return $view->setTemplate('application/pages/vouching/what_is_the_voucher_dob');
    }

    public function enterPostcodeAction(): ViewModel|Response
    {
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $view = new ViewModel();
        $form = $this->createForm(Postcode::class);
        $view->setVariable('details_data', $detailsData);
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
                        'root/voucher_select_address',
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

        return $view->setTemplate('application/pages/vouching/enter_postcode');
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
            'vouching_for' => $detailsData["vouchingFor"] ?? null,
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

            $this->opgApiService->addSelectedAddress($uuid, $structuredAddress);

            return $this->redirect()->toRoute('root/voucher_enter_address_manual', ['uuid' => $uuid]);
        }

        return $view->setTemplate('application/pages/vouching/select_address');
    }

    public function enterAddressManualAction(): ViewModel|Response
    {
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $form = $this->createForm(AddressInput::class);
        $form->setData($detailsData['address'] ?? []);

        $countryList = $this->siriusApiService->getCountryList($this->getRequest());
        $view->setVariable('country_list', $countryList);

        if ($this->getRequest()->isPost()) {
            $formData = $this->getRequest()->getPost();
            $form->setData($formData);

            if ($form->isValid()) {
                $addressMatch = false;
                foreach ($detailsData['lpas'] as $lpa) {
                    $lpasData = $this->siriusApiService->getLpaByUid($lpa, $this->getRequest());
                    $addressMatch = $addressMatch || $this->voucherMatchLpaActorHelper->checkAddressDonorMatch(
                        $lpasData,
                        $this->formToArray($form)
                    );
                }
                if ($addressMatch) {
                    $view->setVariable('address_match', true);
                } else {
                    $this->opgApiService->addSelectedAddress($uuid, $this->formToArray($form));
                    return $this->redirect()->toRoute($this->routes["confirm_donors"], ['uuid' => $uuid]);
                }
            }
        }

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('form', $form);

        return $view->setTemplate('application/pages/vouching/enter_address_manual');
    }

    public function confirmDonorsAction(): ViewModel|Response
    {
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        if ($this->getRequest()->isPost()) {
            /**
            * @psalm-suppress PossiblyUndefinedArrayOffset
            */
            if ($detailsData['idMethodIncludingNation']['id_route'] === 'POST_OFFICE') {
                return $this->redirect()->toRoute("root/find_post_office_branch", ['uuid' => $uuid]);
            }
        }

        $lpaDetails = [];
        foreach ($detailsData['lpas'] as $lpa) {
            /**
            * @psalm-suppress ArgumentTypeCoercion
            */
            $lpaData = $this->siriusApiService->getLpaByUid($lpa, $this->request);

            $donorName = AddDonorFormHelper::getDonorNameFromSiriusResponse($lpaData);
            $type = LpaTypes::fromName(
                $lpaData['opg.poas.lpastore']['lpaType'] ?? $lpaData['opg.poas.sirius']['caseSubtype']
            );

            $lpaDetails[$lpa] = [
                'name' => $donorName,
                'type' => $type,
            ];
        }

        $view = new ViewModel();

        $view->setVariable('lpa_count', count($detailsData['lpas']));
        $view->setVariable('details_data', $detailsData);
        $view->setVariable('lpa_details', $lpaDetails);
        $view->setVariable('case_uuid', $uuid);

        return $view->setTemplate('application/pages/vouching/confirm_donors');
    }

    public function addDonorAction(): ViewModel|Response
    {
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $form = $this->createForm(AddDonor::class);

        $view = new ViewModel();
        $view->setVariable('details_data', $detailsData);
        $view->setVariable('form', $form);
        $view->setVariable('case_uuid', $uuid);

        if ($this->getRequest()->isPost() && $form->isValid()) {
            $formData = $this->getRequest()->getPost()->toArray();

            if (isset($formData['lpas'])) {
                if (isset($formData['declaration'])) {
                    foreach ($formData['lpas'] as $lpa) {
                        $this->opgApiService->updateCaseWithLpa($uuid, $lpa);
                    }
                    return $this->redirect()->toRoute($this->routes["confirm_donors"], ['uuid' => $uuid]);
                } else {
                    $form->setMessages([
                        'declaration' => [
                            "Confirm declaration to continue",
                        ],
                    ]);
                }
            }
            $lpas = $this->siriusApiService->getAllLinkedLpasByUid(
                $formData['lpa'],
                $this->getRequest()
            );

            $processed = $this->addDonorFormHelper->processLpas($lpas, $detailsData);

            $view->setVariable('lpa_response', $processed);
        }
        return $view->setTemplate('application/pages/vouching/vouch_for_another_donor');
    }

    public function removeLpaAction(): Response
    {
        $uuid = $this->params()->fromRoute("uuid");
        $lpa = $this->params()->fromRoute("lpa");

        $this->opgApiService->updateCaseWithLpa($uuid, $lpa, true);

        return $this->redirect()->toRoute($this->routes["confirm_donors"], ['uuid' => $uuid]);
    }

    public function identityCheckPassedAction(): ViewModel
    {
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid, true);

        $view = new ViewModel();

        $view->setVariable('details_data', $detailsData);

        return $view->setTemplate('application/pages/vouching/identity_check_passed');
    }
}

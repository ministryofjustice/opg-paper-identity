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
use Application\Helpers\SiriusDataProcessorHelper;
use Application\Model\Entity\CaseData;
use Application\Services\SiriusApiService;
use Application\Helpers\AddressProcessorHelper;
use Application\Helpers\FormProcessorHelper;
use Application\Helpers\AddDonorFormHelper;
use Application\Helpers\VoucherMatchLpaActorHelper;
use Application\Forms\Postcode;
use Application\Forms\AddressJson;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Application\Enums\IdMethod as IdMethodEnum;
use DateTime;
use Application\Controller\Trait\DobOver100WarningTrait;

class VouchingFlowController extends AbstractActionController
{
    use FormBuilder;
    use DobOver100WarningTrait;

    protected $plugins;

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
        private readonly SiriusDataProcessorHelper $siriusDataProcessorHelper,
        private readonly string $siriusPublicUrl,
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
                return $this->redirect()->toRoute("root/how_will_you_confirm", ['uuid' => $uuid]);
            }
        }
        return $view->setTemplate('application/pages/vouching/confirm_vouching');
    }

    public function voucherNameAction(): ViewModel|Response
    {
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $form = $this->createForm(VoucherName::class);

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('form', $form);

        if ($this->getRequest()->isPost()) {
            if ($form->isValid()) {
                $formData = $this->formToArray($form);

                $match = false;
                foreach ($detailsData['lpas'] as $lpa) {
                    $lpasData = $this->siriusApiService->getLpaByUid($lpa, $this->getRequest());
                    if (empty($lpasData)) {
                        continue;
                    }
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
        } else {
            $form->setData([
                "firstName" => $detailsData["firstName"],
                "lastName" => $detailsData["lastName"]
            ]);
        }

        return $view->setTemplate('application/pages/vouching/what_is_the_voucher_name');
    }

    private function checkMatchesForLpas(array $detailsData, string $dateOfBirth): bool | array
    {
        $match = false;
        foreach ($detailsData['lpas'] as $lpa) {
            $lpasData = $this->siriusApiService->getLpaByUid($lpa, $this->getRequest());
            if (empty($lpasData)) {
                continue;
            }
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
                            if (isset($detailsData["address"])) {
                                return $this->redirect()
                                    ->toRoute("root/voucher_enter_address_manual", ['uuid' => $uuid]);
                            } else {
                                return $this->redirect()
                                    ->toRoute("root/voucher_enter_postcode", ['uuid' => $uuid]);
                            }
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
        $view->setVariable(
            'warning_message',
            'By continuing, you confirm that the person vouching is more than 100 years old. 
            If not, please change the date.'
        );
        return $view->setTemplate('application/pages/confirm_dob');
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
                    if (empty($lpasData)) {
                        continue;
                    }
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
            $idRoute = '';
            $idMethod = '';
            if (isset($detailsData['idMethodIncludingNation'])) {
                $idRoute = $detailsData['idMethodIncludingNation']['id_route'] ?? '';
                $idMethod = $detailsData['idMethodIncludingNation']['id_method'] ?? '';
            }
            $redirect = false;
            if ($idRoute === 'POST_OFFICE') {
                $redirect = "root/find_post_office_branch";
            } else {
                switch ($idMethod) {
                    case IdMethodEnum::PassportNumber->value:
                        $redirect = "root/passport_number";
                        break;
                    case IdMethodEnum::DrivingLicenseNumber->value:
                        $redirect = "root/driving_licence_number";
                        break;
                    case IdMethodEnum::NationalInsuranceNumber->value:
                        $redirect = "root/national_insurance_number";
                        break;
                    default:
                        break;
                }
            }
            if ($redirect) {
                return $this->redirect()->toRoute($redirect, ['uuid' => $uuid]);
            }
        }

        $view = new ViewModel();
        $view->setVariable('lpa_count', count($detailsData['lpas']));
        $view->setVariable('details_data', $detailsData);
        $view->setVariable(
            'lpa_details',
            $this->siriusDataProcessorHelper->createLpaDetailsArray($detailsData, $this->request)
        );
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
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        if ($this->getRequest()->isPost()) {
            $this->redirect()->toUrl($this->siriusPublicUrl . '/lpa/frontend/lpa/' . $detailsData["lpas"][0]);
        }

        $view = new ViewModel();
        $view->setVariable('details_data', $detailsData);

        return $view->setTemplate('application/pages/vouching/identity_check_passed');
    }
}

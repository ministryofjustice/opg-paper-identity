<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Forms\VoucherBirthDate;
use Application\Forms\ConfirmVouching;
use Application\Forms\VoucherName;
use Application\Services\SiriusApiService;
use Application\Helpers\FormProcessorHelper;
use Application\Helpers\VoucherMatchLpaActorHelper;
use Application\Forms\IdMethod;
use Application\Forms\PassportDateCp;
use Laminas\Form\FormInterface;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Application\Enums\IdMethod as IdMethodEnum;

class VouchingFlowController extends AbstractActionController
{
    use FormBuilder;

    protected $plugins;
    private string $uuid;

    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly SiriusApiService $siriusApiService,
        private readonly FormProcessorHelper $formProcessorHelper,
        private readonly VoucherMatchLpaActorHelper $voucherMatchLpaActorHelper,
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
        $view->setVariable('vouching_for', $detailsData["vouchingFor"] ?? null);
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
        $view->setVariable('vouching_for', $detailsData["vouchingFor"] ?? null);
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

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('vouching_for', $detailsData["vouchingFor"] ?? null);
        $view->setVariable('form', $form);

        if ($this->getRequest()->isPost()) {
            $formData = $this->getRequest()->getPost();

            if ($form->isValid()) {
                $matches = [];
                foreach ($detailsData['lpas'] as $lpa) {
                    $lpasData = $this->siriusApiService->getLpaByUid($lpa, $this->getRequest());
                    $matches = array_merge($matches, $this->voucherMatchLpaActorHelper->checkMatch(
                        $lpasData,
                        $formData["firstName"],
                        $formData["lastName"],
                    ));
                }
                if ($matches && ! isset($formData["continue-after-warning"])) {
                    // if there are multiple matches we will only warn about the first
                    $view->setVariable('matches', reset($matches));
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

    public function voucherDobAction(): ViewModel|Response
    {
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);
        $form = $this->createForm(VoucherBirthDate::class);

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('vouching_for', $detailsData["vouchingFor"] ?? null);

        if ($this->getRequest()->isPost()) {
            $formData = $this->getRequest()->getPost();
            $dateOfBirth = $this->formProcessorHelper->processDateForm($formData->toArray());
            $formData->set('date', $dateOfBirth);
            $form->setData($formData);
            $view->setVariable('form', $form);

            if ($form->isValid()) {
                $matches = [];
                foreach ($detailsData['lpas'] as $lpa) {
                    $lpasData = $this->siriusApiService->getLpaByUid($lpa, $this->getRequest());
                    $matches = array_merge($matches, $this->voucherMatchLpaActorHelper->checkMatch(
                        $lpasData,
                        $detailsData["firstName"],
                        $detailsData["lastName"],
                        $dateOfBirth,
                    ));
                }
                if ($matches) {
                    // if there are multiple matches we will only warn about the first
                    $view->setVariable('match', reset($matches));
                } else {
                    try {
                        $this->opgApiService->updateCaseSetDob($uuid, $dateOfBirth);
                        // will need to update to route to next page once built
                        return $this->redirect()->toRoute("root/voucher_dob", ['uuid' => $uuid]);
                    } catch (\Exception $exception) {
                        $form->setMessages(["There was an error saving the data"]);
                    }
                }
            }
        }
        return $view->setTemplate('application/pages/vouching/what_is_the_voucher_dob');
    }

    public function identityCheckPassedAction(): ViewModel
    {
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $view = new ViewModel();

        $view->setVariable('details_data', $detailsData);

        return $view->setTemplate('application/pages/vouching/identity_check_passed');
    }
}

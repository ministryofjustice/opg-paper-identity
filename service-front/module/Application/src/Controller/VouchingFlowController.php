<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Forms\ConfirmVouching;
use Application\Forms\VoucherName;
use Application\Services\SiriusApiService;
use Application\Helpers\VoucherMatchLpaActorHelper;
use Application\Forms\IdMethod;
use Application\Forms\PassportDateCp;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Application\Enums\IdMethod as IdMethodEnum;

class VouchingFlowController extends AbstractActionController
{
    use FormBuilder;

    protected $plugins;

    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly SiriusApiService $siriusApiService,
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
                return $this->redirect()->toUrl(
                    "/start?personType=donor&lpas[]=" . implode("&lpas[]=", $detailsData['lpas'])
                );
            }

            if ($form->isValid()) {
                return $this->redirect()->toRoute("root/vouching_how_will_you_confirm", ['uuid' => $uuid]);
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
        $view->setVariable('vouching_for', $detailsData["vouchingFor"] ?? null);
        $view->setVariable('form', $form);

        if ($this->getRequest()->isPost()) {
            $formData = $this->getRequest()->getPost();

            if ($form->isValid()) {
                $matches = [];
                foreach ($detailsData['lpas'] as $lpa) {
                    $lpasData = $this->siriusApiService->getLpaByUid($lpa, $this->getRequest());
                    $matches = array_merge($matches, $this->voucherMatchLpaActorHelper->checkNameMatch(
                        $formData["firstName"],
                        $formData["lastName"],
                        $lpasData
                    ));
                }
                // this does mean that if they change from one matching name to another they would still get through.
                if ($matches && ! isset($formData["continue-after-warning"])) {
                    $view->setVariable('matches', $matches);
                    $view->setVariable('matched_name', $formData["firstName"] . ' ' . $formData["lastName"]);
                } else {
                    // will need to update to route to next page once built
                    return $this->redirect()->toRoute("root/voucher_name", ['uuid' => $uuid]);
                }
            }
        }
        return $view->setTemplate('application/pages/vouching/what_is_the_voucher_name');
    }

    public function howWillYouConfirmAction(): ViewModel|Response
    {
        $templates = ['default' => 'application/pages/vouching/how_will_you_confirm'];
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $dateSubForm = $this->createForm(PassportDateCp::class);
        $form = $this->createForm(IdMethod::class);
        $view->setVariable('date_sub_form', $dateSubForm);

        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $serviceAvailability = $this->opgApiService->getServiceAvailability($uuid);

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
                        // TODO: This will need to be changed to the actual voucher version of post office documents
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
                        return $this->redirect()->toRoute("root/voucher_name", ['uuid' => $uuid]);
                    }
                }
            }
        }

        $view->setVariable('options_data', $optionsData);
        $view->setVariable('details_data', $detailsData);
        $view->setVariable('uuid', $uuid);

        return $view->setTemplate($templates['default']);
    }
}

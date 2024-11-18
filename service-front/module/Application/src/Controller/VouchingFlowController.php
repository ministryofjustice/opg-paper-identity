<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Forms\ConfirmVouching;
use Application\Forms\VoucherName;
use Application\Services\SiriusApiService;
use Application\Helpers\VoucherMatchLpaActorHelper;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class VouchingFlowController extends AbstractActionController
{
    use FormBuilder;

    protected $plugins;

    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
        private readonly SiriusApiService $siriusApiService,
        private readonly VoucherMatchLpaActorHelper $voucherMatchLpaActorHelper
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
                // will need to update to route for vouching how-will-you-confirm page once built
                return $this->redirect()->toRoute("root/confirm_vouching", ['uuid' => $uuid]);
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

    public function identityCheckPassedAction(): ViewModel
    {
        $uuid = $this->params()->fromRoute("uuid");
        $detailsData = $this->opgApiService->getDetailsData($uuid);

        $view = new ViewModel();

        $view->setVariable('details_data', $detailsData);

        return $view->setTemplate('application/pages/vouching/identity_check_passed');
    }
}

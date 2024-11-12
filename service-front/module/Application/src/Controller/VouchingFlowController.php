<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Application\Controller\Trait\FormBuilder;
use Application\Forms\ConfirmVouching;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class VouchingFlowController extends AbstractActionController
{
    use FormBuilder;

    protected $plugins;

    public function __construct(
        private readonly OpgApiServiceInterface $opgApiService,
    ) {
    }

    public function confirmVouchingAction(): ViewModel|Response
    {
        $view = new ViewModel();
        $uuid = $this->params()->fromRoute("uuid");
        $details_data = $this->opgApiService->getDetailsData($uuid);
        $form = $this->createForm(ConfirmVouching::class);

        $view->setVariable('details_data', $details_data);
        /**
         * @psalm-suppress InvalidArrayOffset
         */
        $view->setVariable('vouching_for', $details_data["vouchingFor"]);
        $view->setVariable('form', $form);

        if ($this->getRequest()->isPost()) {
            $formData = $this->getRequest()->getPost();

            if (isset($formData['try_different'])) {
                // could not get the toRoute method to work?!
                // need to test with multiple lpas (or do we only ever use the first one??)
                return $this->redirect()->toUrl(
                    "/start?personType=donor&lpas[]=" . implode("lpas[]=", $details_data['lpas'])
                );
            }

            if ($form->isValid()) {
                // will need to update to route to vouching how-will-you-confirm page
                return $this->redirect()->toRoute("root/confirm_vouching", ['uuid' => $uuid]);
            }
        }
        return $view->setTemplate('application/pages/vouching/confirm_vouching');
    }
}

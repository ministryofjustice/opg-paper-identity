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
        $donor_details =  $this->opgApiService->getDetailsData($uuid);
        $form = $this->createForm(ConfirmVouching::class);

        $view->setVariable('details_data', $donor_details);
        $view->setVariable('form', $form);

        if ($this->getRequest()->isPost() && $form->isValid()) {
            $formData = $this->getRequest()->getPost();
            var_dump($formData);
            if ($formData['eligibility'] == "eligibility_confirmed" && $formData['declaration'] == "declaration_confirmed") {
                // will need to update to route to vouching how-will-you-confirm page
                return $this->redirect()->toRoute("root/confirm_vouching", ['uuid' => $uuid]);
            }
        }

        return $view->setTemplate('application/pages/vouching/confirm_vouching');
    }

}

<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Application\Forms\NationalInsuranceNumber;
use Laminas\Form\Annotation\AttributeBuilder;

class IndexController extends AbstractActionController
{
    protected $plugins;

    public function __construct(private readonly OpgApiServiceInterface $opgApiService)
    {
    }

    public function indexAction()
    {
        return new ViewModel();
    }

    public function donorIdCheckAction(): ViewModel
    {
        $optionsdata = $this->opgApiService->getIdOptionsData();
        $detailsData = $this->opgApiService->getDetailsData();

        $view = new ViewModel();

        $view->setVariable('options_data', $optionsdata);
        $view->setVariable('details_data', $detailsData);

        return $view->setTemplate('application/pages/donor_id_check');
    }

    public function donorLpaCheckAction(): ViewModel
    {
        $data = $this->opgApiService->getLpasByDonorData();

        $view = new ViewModel();

        $view->setVariable('data', $data);

        return $view->setTemplate('application/pages/donor_lpa_check');
    }

    public function addressVerificationAction(): ViewModel
    {
        $data = $this->opgApiService->getAddressVerificationData();

        $view = new ViewModel();

        $view->setVariable('options_data', $data);

        return $view->setTemplate('application/pages/address_verification');
    }

    public function nationalInsuranceNumberAction(): ViewModel
    {
        $view = new ViewModel();

        $form = (new AttributeBuilder())->createForm(NationalInsuranceNumber::class);
        /**
         * @psalm-suppress UndefinedInterfaceMethod
         */
        if (count($this->getRequest()->getPost())) {
            $formData = $this->getRequest()->getPost();
            $form->setData($formData);
            $validFormat = $form->isValid();

            if ($validFormat) {
                $validNino = $this->opgApiService->checkNinoValidity($formData['nino']);
                if ($validNino) {
                    $this->redirect()->toRoute('national_insurance_number_success', ['controller'
                    => 'IndexController', 'action' => 'nationalInsuranceNumberSuccess']);
                } else {
                    $this->redirect()->toRoute('national_insurance_number_fail', ['controller'
                    => 'IndexController', 'action' => 'nationalInsuranceNumberFail']);
                }
            }
        }

        $detailsData = $this->opgApiService->getDetailsData();

        $view->setVariable('details_data', $detailsData);
        $view->setVariable('form', $form);

        return $view->setTemplate('application/pages/national_insurance_number');
    }

    public function nationalInsuranceNumberSuccessAction(): ViewModel
    {
        $view = new ViewModel();
        return $view->setTemplate('application/pages/national_insurance_number_success');
    }

    public function nationalInsuranceNumberFailAction(): ViewModel
    {
        $view = new ViewModel();
        return $view->setTemplate('application/pages/national_insurance_number_fail');
    }
}

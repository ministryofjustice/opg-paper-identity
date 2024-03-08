<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

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
        $data = $this->opgApiService->getAddresVerificationData();

        $view = new ViewModel();

        $view->setVariable('options_data', $data);

        return $view->setTemplate('application/pages/address_verification');
    }
}

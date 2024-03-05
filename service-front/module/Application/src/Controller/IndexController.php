<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Application\Services\OpgApiService;

class IndexController extends AbstractActionController
{
    public function __construct(private readonly OpgApiServiceInterface $opgApiService)
    {
    }

    public function indexAction()
    {
        return new ViewModel();
    }

    public function donorIdCheckAction()
    {
        $optionsdata = $this->opgApiService->getIdOptionsData();
        $detailsData = $this->opgApiService->getDetailsData();

        $view = new ViewModel();

        $view->setVariable('options_data', $optionsdata);
        $view->setVariable('details_data', $detailsData);

        return $view->setTemplate('application/pages/donor_id_check');
    }

    public function donorLpaCheckAction()
    {
//        $data = $this->opgApiService->getIdOptionsData();

        $view = new ViewModel($data);

        $view->setVariable('data', $data);

        return $view->setTemplate('application/pages/donor_id_check');
    }
}

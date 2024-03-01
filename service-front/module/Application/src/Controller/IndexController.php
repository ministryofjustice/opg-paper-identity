<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Contracts\OpgApiServiceInterface;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Application\Services\OpgApiService;

class IndexController extends AbstractActionController
{
    function __construct(private readonly OpgApiServiceInterface $opgApiService) {}

    public function indexAction()
    {
        return new ViewModel();
    }

    public function pageOneAction()
    {
        $data = $this->opgApiService->getIdOptionsData();

        $view = new ViewModel($data);

        $view->setVariable('data', $data);

        return $view->setTemplate('application/pages/page_one');
    }
}

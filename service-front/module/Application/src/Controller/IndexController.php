<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        return new ViewModel();
    }

    public function applicationAction()
    {
        return new ViewModel();
    }

    public function pageOneAction()
    {
        $data = [
            'Passport',
            'Driving Licence',
            'National Insurance Number'
        ];

        $view = new ViewModel($data);

        $view->setVariable('data', $data);

        return $view->setTemplate('application/pages/page_one');
    }
}

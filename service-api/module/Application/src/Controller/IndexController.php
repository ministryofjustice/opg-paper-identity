<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        $data = array('Laminas' => 'Paper ID Service API');
       return new JsonModel($data);
    }
}

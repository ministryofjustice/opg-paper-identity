<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        error_log('>>cert file size:' . filesize('/opg-private/experian-iiq-cert.pem'));

        $data = ['Laminas' => 'Paper ID Service API'];
        return new JsonModel($data);
    }
}

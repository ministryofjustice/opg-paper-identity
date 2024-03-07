<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Fixtures\DataQueryHandler;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;

class IdentityController extends AbstractActionController
{
    public function __construct(private readonly DataQueryHandler $dataQueryHandler)
    {
    }
    public function indexAction()
    {
        return new JsonModel();
    }

    public function createAction()
    {
    }

    public function methodAction()
    {
        $data = [
            'Passport',
            'Driving Licence',
            'National Insurance Number'
        ];

        return new JsonModel($data);
    }

    public function detailsAction()
    {
        $data = [
            'Name' => 'Mary Anne Chapman',
            'DOB' => '01 May 1943',
            'Address' => 'Address line 1, line 2, Country, BN1 4OD',
            'Role' => 'Donor',
            'LPA' => ['PA M-1234-ABCB-XXXX', 'PW M-1234-ABCD-AAAA']
        ];

        return new JsonModel($data);
    }

    public function testdataAction()
    {

        $data = $this->dataQueryHandler->queryByName("Joe Blogs");

        return new JsonModel($data);
    }
}


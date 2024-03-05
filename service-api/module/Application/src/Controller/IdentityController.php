<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Application\Utilities\PopulateDynomoData;
use Aws\DynamoDb\DynamoDbClient;
use Application\Aws\DynamoDbClientFactory;

class IdentityController extends AbstractActionController
{
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
        $config = [
            'aws' => [
                'debug' => filter_var(getenv('PAPER_ID_BACK_AWS_DEBUG'), FILTER_VALIDATE_BOOLEAN),
                'endpoint' => getenv('PAPER_ID_BACK_AWS_ENDPOINT') ?: 'http://localstack:4566',
                'region' => getenv('AWS_REGION') ?: "eu-west-1",
            ],
        ];
        $dynamoDbClient = new DynamoDbClient($config['aws']);

        $dataLoadService = new PopulateDynomoData($dynamoDbClient);
        $data = $dataLoadService->run();

        return new JsonModel($data);
    }
}


<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Fixtures\DataImportHandler;
use Application\Fixtures\DataQueryHandler;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;

class IdentityController extends AbstractActionController
{

    public function __construct(
        /**
         * @psalm-suppress PropertyNotSetInConstructor
         */
        private readonly DataQueryHandler $dataQueryHandler,
        private readonly DataImportHandler $dataImportHandler,
    ) {
    }

    public function indexAction(): JsonModel
    {
        return new JsonModel();
    }

    public function createAction(): void
    {
    }

    public function methodAction(): JsonModel
    {
        $data = [
            'Passport',
            'Driving Licence',
            'National Insurance Number'
        ];

        /**
         * @psalm-suppress InvalidArgument
         * @see https://github.com/laminas/laminas-view/issues/239
         */
        return new JsonModel($data);
    }

    public function detailsAction(): JsonModel
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

    public function testdataAction(): JsonModel
    {
        $this->dataImportHandler->load();
        $data = $this->dataQueryHandler->returnAll();

        /**
         * @psalm-suppress InvalidArgument
         * @see https://github.com/laminas/laminas-view/issues/239
         */
        return new JsonModel($data);
    }

    public function findByNameAction(): JsonModel
    {
        $data = $this->dataQueryHandler->queryByName("Joe Blogs");
        /**
         * @psalm-suppress InvalidArgument
         * @see https://github.com/laminas/laminas-view/issues/239
         */
        return new JsonModel($data);
    }

    public function findByIdNumberAction(): JsonModel
    {
        $data = $this->dataQueryHandler->queryByIDNumber("HA1483fs528");
        /**
         * @psalm-suppress InvalidArgument
         * @see https://github.com/laminas/laminas-view/issues/239
         */
        return new JsonModel($data);

    }
}

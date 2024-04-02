<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Nino\ValidatorInterface;
use Application\DrivingLicense\ValidatorInterface as LicenseValidatorInterface;
use Application\Fixtures\DataImportHandler;
use Application\Fixtures\DataQueryHandler;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 * Needed here due to false positive from Laminas’s uninitialised properties
 * @psalm-suppress InvalidArgument
 * @see https://github.com/laminas/laminas-view/issues/239
 */
class IdentityController extends AbstractActionController
{
    public function __construct(
        private readonly ValidatorInterface $ninoService,
        private readonly DataQueryHandler $dataQueryHandler,
        private readonly DataImportHandler $dataImportHandler,
        private readonly LicenseValidatorInterface $licenseValidator
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
        /** @var string $name */
        $name = $this->getRequest()->getQuery('username');
        $data = $this->dataQueryHandler->queryByName($name);
        /**
         * @psalm-suppress InvalidArgument
         * @see https://github.com/laminas/laminas-view/issues/239
         */
        return new JsonModel($data);
    }

    public function findByIdNumberAction(): JsonModel
    {
        /** @var string $id */
        $id = $this->getRequest()->getQuery('id');
        $data = $this->dataQueryHandler->queryByIDNumber($id);
        /**
         * @psalm-suppress InvalidArgument
         * @see https://github.com/laminas/laminas-view/issues/239
         */
        return new JsonModel($data);
    }
    public function addressVerificationAction(): JsonModel
    {
        $data = [
            'Passport',
            'Driving Licence',
            'National Insurance Number',
            'Voucher',
            'Post Office',
        ];

        return new JsonModel($data);
    }

    public function listLpasAction(): JsonModel
    {
        $data = [
            [
                'lpa_ref' => 'PW M-1234-ABCD-AAAA',
                'donor_name' => 'Mary Anne Chapman'
            ],
            [
                'lpa_ref' => 'PA M-1234-ABCD-XXXX',
                'donor_name' => 'Mary Anne Chapman'
            ]
        ];

        return new JsonModel($data);
    }

    public function verifyNinoAction(): JsonModel
    {
        $data = $this->getRequest()->getPost();
        $ninoStatus = $this->ninoService->validateNINO($data['nino']);

        $response = [
            'status' => $ninoStatus,
            'nino' => $data['nino']
        ];

        if ($ninoStatus === 'NINO check complete') {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);
        } else {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
        }

        return new JsonModel($response);
    }

    public function validateDrivingLicenceAction(): JsonModel
    {
        $data = $this->getRequest()->getPost();
        $licenseStatus = $this->licenseValidator->validateDrivingLicense($data['dln']);

        $response = [
            'status' => $licenseStatus,
            'driving_license' => $data['dln']
        ];

        if ($licenseStatus === 'PASS') {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);
        } else {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
        }

        return new JsonModel($response);
    }

    public function validatePassportAction(): JsonModel
    {
        $validDrivingLicences = ['123456789'];

        $data = $this->getRequest()->getPost();

        if (in_array($data['passport'], $validDrivingLicences)) {
            $response = [
                'status' => 'valid',
                'driving_licence' => $data['passport']
            ];
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);
        } else {
            $response = [
                'status' => 'not valid',
                'driving_licence' => $data['passport']
            ];
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
        }

        return new JsonModel($response);
    }
}

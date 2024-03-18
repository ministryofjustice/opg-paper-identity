<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;

/**
 * @psalm-suppress InvalidArgument
 * @see https://github.com/laminas/laminas-view/issues/239
 */
class IdentityController extends AbstractActionController
{
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

    public function validateNinoAction(): JsonModel
    {
        $validNinos = ['AA112233A'];

        $data = $this->getRequest()->getPost();

        if (in_array($data['nino'], $validNinos)) {
            $response = [
                'status' => 'valid',
                'nino' => $data['nino']
            ];
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);
        } else {
            $response = [
                'status' => 'not valid',
                'nino' => $data['nino']
            ];
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
        }

        return new JsonModel($response);
    }

    public function validateDrivingLicenceAction(): JsonModel
    {
        $validDrivingLicences = ['JONES710238HA3DX'];

        $data = $this->getRequest()->getPost();

        if (in_array($data['driving_licence'], $validDrivingLicences)) {
            $response = [
                'status' => 'valid',
                'driving_licence' => $data['driving_licence']
            ];
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);
        } else {
            $response = [
                'status' => 'not valid',
                'driving_licence' => $data['driving_licence']
            ];
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_400);
        }

        return new JsonModel($response);
    }
}

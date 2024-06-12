<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Fixtures\DataQueryHandler;
use Application\Fixtures\DataImportHandler;
use Application\Model\Entity\CaseData;

class LpaHelper
{
    private array $responses = [
        "aa" => [
            "status" => "Already Added",
            "error" => "This LPA has already been added to this ID check."
        ],
        "nf" => [
            "status" => "Not Found",
            "error" => "No LPA found."
        ],
        "nm" => [
            "status" => "No Match",
            "error" => "This LPA cannot be added to this ID check because the
                certificate provider details on this LPA do not match.
                Edit the certificate provider record in Sirius if appropriate and find again.",
            "additional_data" => ""
        ],
        "ac" => [
            "status" => "Already Complete",
            "error" => "This LPA cannot be added as an ID check has already been
                 completed for this LPA."
        ],
        "dd" => [
            "status" => "Draft",
            "error" => "This LPA cannot be added as it’s status is set to Draft.
                LPAs need to be in the In Progress status to be added to this ID check."
        ],
        "ol" => [
            "status" => "Started Online",
            "error" => "This LPA cannot be added to this identity check because
                the certificate provider has signed this LPA online."
        ],
    ];

    public function __construct(
        private readonly DataQueryHandler $dataQueryHandler,
        private readonly DataImportHandler $dataImportHandler,
    ) {
    }

    public function getLpasByUuid(string $uuid): array|null
    {
        $caseData = $this->dataQueryHandler->getCaseByUUID($uuid);

        if ($caseData) {
            return $caseData->lpas;
        } else {
            return null;
        }
    }

    public function getLpaByLpa(string $lpa): array|null
    {
        $caseData = $this->dataQueryHandler->getCaseByUUID($uuid);

        if ($caseData) {
            return $caseData->lpas;
        } else {
            return null;
        }
    }

    public function addLpaByCase(string $uuid, string $lpa): CaseData
    {
    }

    public function removeLpaByCase(string $uuid, string $lpa): CaseData
    {
    }


    public function findLpaAction(): JsonModel
    {
        $uuid = $this->params()->fromRoute('uuid');
        $lpa = $this->params()->fromRoute('lpa');
        $status = Response::STATUS_CODE_200;

        $response = [];

        //pending design decision - may need this code

        //        if($lpa == null || $lpa == '') {
        //            $status = Response::STATUS_CODE_400;
        //            $message = "Enter an LPA number to continue.";
        //            $response['message'] = $message;
        //            $response['status'] = $status;
        //            return new JsonModel($response);
        //        }
        //
        //        if (1 !== preg_match('/M(-([0-9A-Z]){4}){3}/', $lpa)) {
        //            $status = Response::STATUS_CODE_400;
        //            $message = "Not a valid LPA number. Enter an LPA number to continue.";
        //            $response['message'] = $message;
        //            $response['status'] = $status;
        //            return new JsonModel($response);
        //        }

        switch ($lpa) {
            case 'M-0000-0000-0000':
                $message = 'Success';
                $response['data'] = [
                    'case_uuid' => $uuid,
                    "LPA_Number" => $lpa,
                    "Type_Of_LPA" => "Personal welfare",
                    "Donor" => "Mary Ann Chapman",
                    "Status" => "Processing",
                    "CP_Name" => "David Smith",
                    "CP_Address" => [
                        'Line_1' => '82 Penny Street',
                        'Line_2' => 'Lancaster',
                        'Town' => 'Lancashire',
                        'PostOfficePostcode' => 'LA1 1XN',
                        'Country' => 'United Kingdom',
                    ],
                ];
                break;
            case 'M-0000-0000-0001':
                $status = Response::STATUS_CODE_400;
                $message = "This LPA has already been added to this ID check.";
                $response['data']['Status'] = 'Already added';
                break;
            case 'M-0000-0000-0002':
                $status = Response::STATUS_CODE_400;
                $message = "No LPA found.";
                $response['data']['Status'] = 'Not found';
                break;
            case 'M-0000-0000-0003':
                $status = Response::STATUS_CODE_400;
                $message = "This LPA cannot be added to this ID check because the
                certificate provider details on this LPA do not match.
                Edit the certificate provider record in Sirius if appropriate and find again.";
                $response['additional_data'] = [
                    'Name' => 'John Brian Adams',
                    'Address' => [
                        'Line_1' => '42 Mount Street',
                        'Line_2' => 'Hednesford',
                        'Town' => 'Cannock',
                        'PostOfficePostcode' => 'WS12 4DE',
                        'Country' => 'United Kingdom',
                    ]
                ];
                break;
            case 'M-0000-0000-0004':
                $status = Response::STATUS_CODE_400;
                $response['data']['Status'] = 'Already completed';
                $message = "This LPA cannot be added as an ID check has already been
                 completed for this LPA.";
                break;
            case 'M-0000-0000-0005':
                $status = Response::STATUS_CODE_400;
                $response['data']['Status'] = 'Draft';
                $message = "This LPA cannot be added as it’s status is set to Draft.
                LPAs need to be in the In Progress status to be added to this ID check.";
                break;
            case 'M-0000-0000-0006':
                $status = Response::STATUS_CODE_400;
                $response['data']['Status'] = 'Online';
                $message = "This LPA cannot be added to this identity check because
                the certificate provider has signed this LPA online.";
                break;
            default:
                /**
                 * @psalm-suppress PossiblyNullReference
                 */
                $case = $this->dataQueryHandler->getCaseByUUID($uuid) ?
                    $this->dataQueryHandler->getCaseByUUID($uuid)->toArray() :
                    [];
                $status = Response::STATUS_CODE_200;
                $message = "Success.";
                $response['data'] = [
                    'case_uuid' => $uuid,
                    "LPA_Number" => $lpa,
                    "Type_Of_LPA" => "Personal welfare",
                    "Donor" => "Mary Ann Chapman",
                    "Status" => "Processing",
                    "CP_Name" => $case['firstName'] . " " . $case['lastName'],
                    "CP_Address" => $case['address']
                ];
                break;
        }
        $this->getResponse()->setStatusCode(Response::STATUS_CODE_200);
        $response['message'] = $message;
        $response['status'] = $status;
        $response['uuid'] = $uuid;
        return new JsonModel($response);
    }
}

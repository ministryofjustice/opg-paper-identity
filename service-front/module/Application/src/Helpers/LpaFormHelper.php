<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Helpers\DTO\LpaFormHelperResponseDto;
use Application\Services\SiriusApiService;
use Laminas\Form\FormInterface;
use Laminas\Stdlib\Parameters;
use Laminas\Http\Response;

class LpaFormHelper
{
    public function findLpa(
        string $uuid,
        Parameters $formData,
        FormInterface $form,
        array $siriusCheck,
        array $detailsData,
    ): LpaFormHelperResponseDto {
        $form->setData($formData);
        $result = [];

        if ($form->isValid()) {
            if (
                ! array_key_exists('opg.poas.lpastore', $siriusCheck) ||
                empty($siriusCheck['opg.poas.lpastore'])
            ) {
                $result = [
                    'status' => 'Not Found',
                    'message' => "No LPA found.",
                ];
                return new LpaFormHelperResponseDto(
                    $uuid,
                    $form,
                    [
                        'lpa_response' => $result
                    ],
                );
            }

            if (! $this->checkLpaNotadded($form->get('lpa')->getValue(), $detailsData)) {
                $result = [
                    'status' => 'Already added',
                    'message' => "This LPA has already been added to this ID check.",
                ];
                return new LpaFormHelperResponseDto(
                    $uuid,
                    $form,
                    [
                        'lpa_response' => $result
                    ],
                );
            }

            $idCheck = $this->compareCpRecords($detailsData, $siriusCheck);
            $statusCheck = $this->checkStatus($siriusCheck);

            if ($statusCheck['error'] === true) {
                $result['status'] = $statusCheck['status'];
                $result['message'] = $statusCheck['message'];
            } elseif ($idCheck['error'] === true) {
                $result['status'] = 'no match';
                $result['message'] = $idCheck['message'];
                $result['additional_data'] = [
                    'name' => $idCheck['name'],
                    'address' => $idCheck['address'],
                    'name_match' => $idCheck['name_match'],
                    'address_match' => $idCheck['address_match'],
                    'error' => $idCheck['error']
                ];
                $result['data'] = [
                    "case_uuid" => $uuid,
                    "LPA_Number" => $form->get('lpa')->getValue(),
                    "Type_Of_LPA" => $this->getLpaTypeFromSiriusResponse($siriusCheck),
                    "Donor" => $this->getDonorNameFromSiriusResponse($siriusCheck),
                    "Status" => $statusCheck['status'],
                    "CP_Name" => $detailsData['firstName'] . " " . $detailsData['lastName'],
                    "CP_Address" => $detailsData['address']
                ];
            } else {
                $result['status'] = "Success";
                $result['message'] = "";
                $result['data'] = [
                    "case_uuid" => $uuid,
                    "LPA_Number" => $form->get('lpa')->getValue(),
                    "Type_Of_LPA" => $this->getLpaTypeFromSiriusResponse($siriusCheck),
                    "Donor" => $this->getDonorNameFromSiriusResponse($siriusCheck),
                    "Status" => $statusCheck['status'],
                    "CP_Name" => $detailsData['firstName'] . " " . $detailsData['lastName'],
                    "CP_Address" => $detailsData['address']
                ];
            }
        }

        return new LpaFormHelperResponseDto(
            $uuid,
            $form,
            [
                'lpa_response' => $result
            ],
        );
    }

    public function getCpAddressFromSiriusResponse(array $siriusCheck): array
    {
        try {
            return $siriusCheck['opg.poas.lpastore']['certificateProvider']['address'];
        } catch (\Exception $exception) {
            return [$exception->getMessage()];
        }
    }

    public function getLpaTypeFromSiriusResponse(array $siriusCheck): string
    {
        try {
            return $siriusCheck['opg.poas.lpastore']['lpaType'];
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }

    public function getDonorNameFromSiriusResponse(array $siriusCheck): string
    {
        try {
            return $siriusCheck['opg.poas.sirius']['donor']['firstname'] .
                " " .
                $siriusCheck['opg.poas.sirius']['donor']['surname'];
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }

    public function compareCpRecords(array $detailsData, array $siriusCheck): array
    {
        $response = [
            'name_match' => false,
            'address_match' => false,
//            'name' => "",
//            'address' => [],
            'error' => false,
            'info' => null
        ];

        try {
            $checkName = $siriusCheck['opg.poas.lpastore']['certificateProvider']['firstNames'] . " " .
                $siriusCheck['opg.poas.lpastore']['certificateProvider']['lastName'];

            $siriusCpAddress = $siriusCheck['opg.poas.lpastore']['certificateProvider']['address'];

            $opgCpAddress = $detailsData['address'];
            $response['name'] = $checkName;
            $response['address'] = $siriusCpAddress;

            if (
                $siriusCpAddress['postcode'] == $opgCpAddress[3] &&
                $siriusCpAddress['line1'] == $opgCpAddress[0]
            ) {
                $response['address_match'] = true;
            } else {
                $response['message'] = "This LPA cannot be added to this ID check because the " .
                    "certificate provider details on this LPA do not match." .
                    "Edit the certificate provider record in Sirius if appropriate and find again.";
            }

            if ($checkName == $detailsData['firstName'] . " " . $detailsData['lastName']) {
                $response['name_match'] = true;
            } else {
                $response['message'] = "This LPA cannot be added to this ID check because the" .
                    " certificate provider details on this LPA do not match. " .
                    "Edit the certificate provider record in Sirius if appropriate and find again.";
            }
            if (! $response['address_match'] || ! $response['name_match']) {
                $response['error'] = true;
            }
        } catch (\Exception $exception) {
            $response['error'] = true;
            $response['info'] = $exception->getMessage();
        }
        return $response;
    }

    public function checkStatus(array $siriusCheck): array
    {
        $response = [
            'error' => false,
            'status' => "",
            'message' => ""
        ];
        if (
            array_key_exists('opg.poas.lpastore', $siriusCheck) &&
            array_key_exists('status', $siriusCheck['opg.poas.lpastore'])
        ) {
            $response['status'] = $siriusCheck['opg.poas.lpastore']['status'];
            if (
                $response['status'] == 'complete' ||
                $response['status'] == 'registered' ||
                $response['status'] == 'in progress'
            ) {
                $response['error'] = true;
                $response['message'] = "This LPA cannot be added as an ID" .
                " check has already been completed for this LPA.";
            }
            if ($response['status'] == 'draft') {
                $response['error'] = true;
                $response['message'] = "This LPA cannot be added as it’s status is set to Draft.
                    LPAs need to be in the In Progress status to be added to this ID check.";
            }
            if ($response['status'] == 'online') {
                $response['error'] = true;
                $response['message'] = "This LPA cannot be added to this identity check because
                    the certificate provider has signed this LPA online.";
            }
        } else {
            $response['error'] = true;
            $response['message'] = "No LPA Found.";
        }
        return $response;
    }

    public function checkLpaNotAdded(string $lpa, array $detailsData): bool
    {
        try {
            foreach ($detailsData['lpas'] as $existingLpa) {
                if ($lpa == $existingLpa) {
                    return false;
                }
            }
            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }
}

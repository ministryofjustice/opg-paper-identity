<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Helpers\DTO\FormProcessorResponseDto;
use Application\Services\SiriusApiService;
use Laminas\Form\FormInterface;
use Laminas\Stdlib\Parameters;

class LpaFormHelper
{
    public function findLpa(
        string $uuid,
        Parameters $formData,
        FormInterface $form,
        array $siriusCheck,
        array $detailsData,
        array $templates = []
    ): FormProcessorResponseDto {
        $form->setData($formData);
        $result = [];

        if ($form->isValid()) {
            $idCheck = $this->compareCpRecords($detailsData, $siriusCheck);
            $statusCheck = $this->checkStatus($siriusCheck);

            if ($idCheck['error'] === false && $statusCheck['error'] === false) {
                $result['status'] = 200;
                $result['message'] = "Success";
                $result['data'] = [
                    "case_uuid" => $uuid,
                    "LPA_Number" => $form->get('lpa'),
                    "Type_Of_LPA" => $this->getLpaTypeFromSiriusResponse($siriusCheck),
                    "Donor" => $this->getDonorNameFromSiriusResponse($siriusCheck),
                    "Status" => $statusCheck['status'],
                    "CP_Name" => $idCheck['name'],
                    "CP_Address" => $this->getCpAddressFromSiriusResponse($siriusCheck)
                ];
            }
        }

        return new FormProcessorResponseDto(
            $uuid,
            $form,
            $templates['default'],
            [
                'lpa_response' => $result
            ],
        );
    }

    public function getCpAddressFromSiriusResponse(array $siriusCheck): string
    {
        try {
            return $siriusCheck['opg.poas.lpastore']['certificateProvider']['address'];
        }   catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }

    public function getLpaTypeFromSiriusResponse(array $siriusCheck): string
    {
        try {
            return $siriusCheck['opg.poas.lpastore']['lpaType'];
        }   catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }

    public function getDonorNameFromSiriusResponse(array $siriusCheck): string
    {
        try {
            return $siriusCheck['opg.poas.sirius']['donor']['firstname'] .
                " " .
                $siriusCheck['opg.poas.sirius']['donor']['surname'];
        }   catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }

    public function compareCpRecords(array $detailsData, array $siriusCheck): array
    {
        $response = [
            'name_match' => false,
            'address_match' => false,
            'name' => "",
            'error' => false,
            'info' => null
        ];

        try {
            $checkName = $siriusCheck['opg.poas.lpastore']['certificateProvider']['firstNames'] . " " .
                $siriusCheck['opg.poas.lpastore']['certificateProvider']['lastName'];

            $siriusCpAddress = $siriusCheck['opg.poas.lpastore']['certificateProvider']['address'];
            $opgCpAddress = $detailsData['address'];

            if (
                $siriusCpAddress['postcode'] == $opgCpAddress[3] &&
                $siriusCpAddress['line1'] == $opgCpAddress[0]
            ) {
                $response['address_match'] = true;
            }

            if ($checkName == $detailsData['firstName'] . " ". $detailsData['lastName']) {
                $response['name_match'] = true;
                $response['name'] = $checkName;
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
                $response['status'] == 'Already Complete' ||
                $response['status'] == 'registered' ||
                $response['status'] == 'in progress'
            ) {
                $response['error'] = true;
                $response['message'] = "This LPA cannot be added as an ID check has already been completed for this LPA.";
            }
            if ($response['status'] == 'Draft') {
                $response['error'] = true;
                $response['message'] = "This LPA cannot be added as it’s status is set to Draft.
                    LPAs need to be in the In Progress status to be added to this ID check.";
            }
            if ($response['status'] == 'Started Online') {
                $response['error'] = true;
                $response['message'] = "This LPA cannot be added to this identity check because
                    the certificate provider has signed this LPA online.";
            }
            if ($response['status'] == 'No Match') {
                $response['error'] = true;
                $response['message'] = "This LPA cannot be added to this ID check because the
                    certificate provider details on this LPA do not match.
                    Edit the certificate provider record in Sirius if appropriate and find again.";
            }
        } else {
            $response['error'] = true;
            $response['message'] = "No LPA Found.";
        }
        return $response;
    }
}

<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Enums\LpaTypes;
use Application\Helpers\DTO\LpaFormHelperResponseDto;
use Laminas\Form\FormInterface;

class LpaFormHelper
{
    /**
     * @param FormInterface<array{lpa: string}> $form
     */
    public function findLpa(
        string $uuid,
        FormInterface $form,
        array $siriusCheck,
        array $detailsData,
    ): LpaFormHelperResponseDto {
        $result = [
            'status' => "",
            'message' => ""
        ];

        if ($form->isValid()) {
            $formData = $form->getData(FormInterface::VALUES_AS_ARRAY);

            if (
                ! array_key_exists('opg.poas.lpastore', $siriusCheck) ||
                empty($siriusCheck['opg.poas.lpastore'])
            ) {
                return new LpaFormHelperResponseDto(
                    $uuid,
                    $form,
                    'Not Found',
                    'No LPA found.'
                );
            }

            $idCheck = $this->compareCpRecords($detailsData, $siriusCheck);
            $statusCheck = $this->checkStatus($siriusCheck);
            $channelCheck = $this->checkChannel($siriusCheck);

            if ($idCheck['error'] === true) {
                $result['status'] = 'no match';
                $result['message'] = $idCheck['message'];
                $result['additional_data'] = [
                    'name' => $idCheck['name'],
                    'address' => $idCheck['address'],
                    'name_match' => $idCheck['name_match'],
                    'address_match' => $idCheck['address_match'],
                    'error' => $idCheck['error']
                ];
            } elseif (! $this->checkLpaNotAdded($formData['lpa'], $detailsData)) {
                $result['status'] = 'error';
                $result['message'] = "This LPA has already been added to this identity check.";
            } elseif ($statusCheck['error'] === true) {
                $result['status'] = 'error';
                $result['message'] = $statusCheck['message'];
            } elseif ($channelCheck['error'] === true) {
                $result['status'] = 'error';
                $result['message'] = $channelCheck['message'];
            } else {
                $result['status'] = "success";
                $result['message'] = "";
            }
            $result['data'] = [
                "case_uuid" => $uuid,
                "lpa_number" => $formData['lpa'],
                "type_of_lpa" => $this->getLpaTypeFromSiriusResponse($siriusCheck),
                "donor" => $this->getDonorNameFromSiriusResponse($siriusCheck),
                "lpa_status" => $statusCheck['status'],
                "cp_name" => $idCheck['name'],
                "cp_address" => $idCheck['address']
            ];
        }

        return new LpaFormHelperResponseDto(
            $uuid,
            $form,
            /**
             * @psalm-suppress PossiblyUndefinedArrayOffset
             */
            $result['status'],
            $result['message'],
            array_key_exists('data', $result) ? $result['data'] : [],
            array_key_exists('additional_data', $result) ? $result['additional_data'] : [],
        );
    }

    private function getLpaTypeFromSiriusResponse(array $siriusCheck): string
    {
        return $siriusCheck['opg.poas.lpastore']['lpaType'] ?? '';
    }

    private function getDonorNameFromSiriusResponse(array $siriusCheck): string
    {
        return implode(' ', [
            $siriusCheck['opg.poas.sirius']['donor']['firstname'] ?? '',
            $siriusCheck['opg.poas.sirius']['donor']['surname'] ?? '',
        ]);
    }

    private function compareCpRecords(array $detailsData, array $siriusCheck): array
    {
        $response = [
            'name_match' => false,
            'address_match' => false,
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
                $siriusCpAddress['postcode'] == $opgCpAddress['postcode'] &&
                $siriusCpAddress['line1'] == $opgCpAddress['line1']
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

    private function checkStatus(array $siriusCheck): array
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
        } else {
            $response['error'] = true;
            $response['message'] = "No LPA Found.";
        }
        return $response;
    }

    private function checkChannel(array $siriusCheck): array
    {
        $response = [];
        $response['channel'] = 'paper';
        $response['error'] = false;
        $response['message'] = "";
        $response['status'] = $siriusCheck['opg.poas.lpastore']['status'];

        if (
            array_key_exists('opg.poas.lpastore', $siriusCheck) &&
            array_key_exists('status', $siriusCheck['opg.poas.lpastore'])
        ) {
            $response['channel'] = $siriusCheck['opg.poas.lpastore']['certificateProvider']['channel'];
            if ($response['channel'] == 'online') {
                $response['error'] = true;
                $response['message'] = "This LPA cannot be added to this identity check because
                    the certificate provider has signed this LPA online.";
            }
        }
        return $response;
    }

    private function checkLpaNotAdded(string $lpa, array $detailsData): bool
    {
        foreach ($detailsData['lpas'] as $existingLpa) {
            if ($lpa == $existingLpa) {
                return false;
            }
        }
        return true;
    }

    public function lpaIdentitiesMatch(array $lpas, string $type): bool
    {
        if (count($lpas) == 1) {
            return true;
        }

        $name = $lpas[0]['opg.poas.lpastore'][$type]['firstNames'] . " " .
            $lpas[0]['opg.poas.lpastore'][$type]['lastName'];

        $address = $lpas[0]['opg.poas.lpastore'][$type]['address']['line1'] . " " .
            $lpas[0]['opg.poas.lpastore'][$type]['address']['postcode'];
        foreach ($lpas as $lpa) {
            $nextname = $lpa['opg.poas.lpastore'][$type]['firstNames'] . " " .
                $lpas[0]['opg.poas.lpastore'][$type]['lastName'];

            $nextAddress = $lpa['opg.poas.lpastore'][$type]['address']['line1'] . " " .
                $lpas[0]['opg.poas.lpastore'][$type]['address']['postcode'];
            if ($name !== $nextname || $address !== $nextAddress) {
                return false;
            }
        }
        return true;
    }

    private function getDonorDobFromSiriusResponse(array $siriusCheck): string
    {
        return $siriusCheck['opg.poas.sirius']['donor']['dob'];
    }

    private function getDonorAddressFromSiriusResponse(array $siriusCheck): string
    {
        return implode('\n', [
            $siriusCheck['opg.poas.sirius']['donor']['addressLine1'],
            $siriusCheck['opg.poas.sirius']['donor']['addressLine2'],
            $siriusCheck['opg.poas.sirius']['donor']['addressLine3'],
            $siriusCheck['opg.poas.sirius']['donor']['town'],
            $siriusCheck['opg.poas.sirius']['donor']['postcode'],
            $siriusCheck['opg.poas.sirius']['donor']['country'],
        ]);
    }

    public function processLpas($lpasData) {

        $lpas = [];
        foreach ($lpasData as $lpa) {
            $lpas[] = [
                "uId" => $lpa["uId"],
                "type" => LpaTypes::fromName( $lpa["opg.poas.sirius"]["caseSubtype"]),
            ];
        }
        return [
            "lpasCount" => count($lpasData),
            "lpas" => $lpas,
            "donorName" => $this->getDonorNameFromSiriusResponse(current($lpasData)),
            "donorDob" => $this->getDonorDobFromSiriusResponse(current($lpasData)),
            "donorAddress" => $this->getDonorAddressFromSiriusResponse(current($lpasData)),
        ];
    }
}

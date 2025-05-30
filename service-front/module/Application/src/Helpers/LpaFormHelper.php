<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Enums\LpaStatusType;
use Application\Enums\PersonType;
use Application\Helpers\LpaStatusTypeHelper;
use Application\Helpers\DTO\LpaFormHelperResponseDto;
use Laminas\Form\FormInterface;

class LpaFormHelper
{
    private const DRAFT_MESSAGE = "This LPA cannot be added as it’s status is set to <b>Draft</b>.
                    LPAs need to be in the <b>In progress</b> status to be added to this ID check.";

    private const STATUS_FAIL_MESSAGE = "These LPAs cannot be added as they do not have the correct status " .
    "for an ID check. LPAs need to be in the <b>In progress</b> status to be added to this identity check.";

    private const NOT_FOUND_MESSAGE = "No LPA found.";

    private const ONLINE_MESSAGE = "This LPA cannot be added to this identity check because
                    the certificate provider has signed this LPA online.";

    private const NO_MATCH_MESSAGE = "This LPA cannot be added to this ID check because the " .
    "certificate provider details on this LPA do not match. " .
    "Edit the certificate provider record in Sirius if appropriate and find again.";

    private const REGISTERED_MESSAGE = "This LPA cannot be added as an identity check has already " .
    "been completed for this LPA";

    /**
     * @param FormInterface<array{lpa: string}> $form
     */
    public function findLpa(
        string $uuid,
        FormInterface $form,
        ?array $siriusCheck,
        array $detailsData,
    ): LpaFormHelperResponseDto {
        $result = [
            'status' => null,
            'messages' => []
        ];

        if ($form->isValid()) {
            $formData = $form->getData(FormInterface::VALUES_AS_ARRAY);

            if (is_null($siriusCheck)) {
                return new LpaFormHelperResponseDto(
                    $uuid,
                    $form,
                    null,
                    [self::NOT_FOUND_MESSAGE]
                );
            }

            if (
                array_key_exists('opg.poas.sirius', $siriusCheck) &&
                (! array_key_exists('opg.poas.lpastore', $siriusCheck) ||
                    empty($siriusCheck['opg.poas.lpastore']))
            ) {
                $data = [
                    "case_uuid" => $uuid,
                    "lpa_number" => $formData['lpa'],
                    "type_of_lpa" => $this->getLpaTypeFromSiriusResponse($siriusCheck),
                    "donor" => $this->getDonorNameFromSiriusResponse($siriusCheck),
                    "lpa_status" => LpaStatusType::Draft,
                ];
                return new LpaFormHelperResponseDto(
                    $uuid,
                    $form,
                    LpaStatusType::Draft,
                    [self::DRAFT_MESSAGE],
                    $data
                );
            }

            $statusCheck = $this->checkStatus($siriusCheck, $detailsData['personType']);
            if ($statusCheck['error'] === true) {
                $result['status'] = $statusCheck['status'];
                $result['messages']['status_check'] = $statusCheck['message'];
            }

            $idCheck = $this->compareCpRecords($detailsData, $siriusCheck);
            $channelCheck = $this->checkChannel($siriusCheck);

            if ($idCheck['error'] === true) {
                $result['status'] = null;
                $result['messages']['id_check'] = $idCheck['message'];
                $result['additional_data']['id_check'] = [
                    'name' => $idCheck['name'],
                    'address' => $idCheck['address'],
                    'name_match' => $idCheck['name_match'],
                    'address_match' => $idCheck['address_match'],
                    'error' => $idCheck['error']
                ];
            } elseif (! $this->checkLpaNotAdded($formData['lpa'], $detailsData)) {
                $result['messages']['duplicate_check'] = "This LPA has already been added to this identity check.";
            } elseif ($channelCheck['error'] === true) {
                $result['messages']['channel_check'] = $channelCheck['message'];
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
            $result['status'],
            $result['messages'],
            array_key_exists('data', $result) ? $result['data'] : [],
            array_key_exists('additional_data', $result) ? $result['additional_data'] : [],
        );
    }

    private function getLpaTypeFromSiriusResponse(array $siriusCheck): string
    {
        return $siriusCheck['opg.poas.lpastore']['lpaType'] ?? $siriusCheck['opg.poas.sirius']['caseSubtype'];
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
                $response['message'] = self::NO_MATCH_MESSAGE;
            }

            if ($checkName == $detailsData['firstName'] . " " . $detailsData['lastName']) {
                $response['name_match'] = true;
            } else {
                $response['message'] = self::NO_MATCH_MESSAGE;
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

    private function checkStatus(array $siriusCheck, PersonType $personType): array
    {
        $response = [
            'error' => false,
            'message' => ""
        ];

        $statusCheck = new LpaStatusTypeHelper($siriusCheck, $personType);

        $response['status'] = $statusCheck->getStatus();

        if ($response['status'] === LpaStatusType::InProgress) {
            return $response;
        }

        if ($response['status'] == LpaStatusType::Draft) {
            $response['error'] = true;
            $response['message'] = self::DRAFT_MESSAGE;

            return $response;
        }

        if ($response['status'] == LpaStatusType::Registered) {
            $response['error'] = true;
            $response['message'] = self::REGISTERED_MESSAGE;

            return $response;
        }

        if (! $statusCheck->isStartable()) {
            $response['error'] = true;
            $response['message'] = self::STATUS_FAIL_MESSAGE;

            return $response;
        }

        $response['error'] = true;
        $response['message'] = self::NOT_FOUND_MESSAGE;
        $response['status'] = null;

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
                $response['message'] = self::ONLINE_MESSAGE;
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

    public function lpaIdentitiesMatch(array $lpas, PersonType $personType): bool
    {
        if (count($lpas) == 1) {
            return true;
        }

        $personTypeKey = $personType === PersonType::CertificateProvider ? 'certificateProvider' : 'donor';

        $name = $lpas[0]['opg.poas.lpastore'][$personTypeValue]['firstNames'] . " " .
            $lpas[0]['opg.poas.lpastore'][$personTypeValue]['lastName'];

        $address = $lpas[0]['opg.poas.lpastore'][$personTypeValue]['address']['line1'] . " " .
            $lpas[0]['opg.poas.lpastore'][$personTypeValue]['address']['postcode'];
        foreach ($lpas as $lpa) {
            $nextname = $lpa['opg.poas.lpastore'][$personTypeValue]['firstNames'] . " " .
                $lpas[0]['opg.poas.lpastore'][$personTypeValue]['lastName'];

            $nextAddress = $lpa['opg.poas.lpastore'][$personTypeValue]['address']['line1'] . " " .
                $lpas[0]['opg.poas.lpastore'][$personTypeValue]['address']['postcode'];
            if ($name !== $nextname || $address !== $nextAddress) {
                return false;
            }
        }
        return true;
    }
}

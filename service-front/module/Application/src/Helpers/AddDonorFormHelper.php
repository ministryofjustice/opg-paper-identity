<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Enums\LpaTypes;
use Application\Enums\LpaActorTypes;
use Application\Helpers\AddressProcessorHelper;
use Application\Helpers\DTO\AddDonorFormHelperResponseDto;
use Application\Helpers\VoucherMatchLpaActorHelper;
use Laminas\Form\FormInterface;
use DateTime;

class AddDonorFormHelper
{

    private function checkStatus(array $lpaData): array
    {
        // i'm confused by which status _wouldnt_ create an error response
        $response = [
            'error' => false,
            'status' => "",
            'message' => ""
        ];
        if (
            array_key_exists('opg.poas.lpastore', $lpaData) &&
            array_key_exists('status', $lpaData['opg.poas.lpastore'])
        ) {
            $response['status'] = $lpaData['opg.poas.lpastore']['status'];
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

    private function checkLpaNotAdded(string $lpa, array $detailsData): bool
    {
        foreach ($detailsData['lpas'] as $existingLpa) {
            if ($lpa == $existingLpa) {
                return false;
            }
        }
        return true;
    }

    private function getDonorNameFromSiriusResponse(array $lpaData): string
    {
        return implode(' ', [
            $lpaData['opg.poas.sirius']['donor']['firstname'] ?? '',
            $lpaData['opg.poas.sirius']['donor']['surname'] ?? '',
        ]);
    }

    private function getDonorDobFromSiriusResponse(array $lpaData): string
    {
        // this is needed as PHP parses dates with mm/dd/yyyy by default
        $dob = implode("-", array_reverse(explode("/", $lpaData["opg.poas.sirius"]["donor"]["dob"])));
        return DateTime::createFromFormat('Y-m-d', $dob)->format('d M Y');
    }

    private function getDonorAddressFromSiriusResponse(array $lpaData): array
    {
        return AddressProcessorHelper::processAddress($lpaData['opg.poas.sirius']['donor'], 'siriusAddressType');
    }

    public function processLpas($lpasData, $detailsData) {

        $response = [
            "lpasCount" => count($lpasData),
            "error" => false,
            "warning" => null,
            "message" => null
        ];

        $matchHelper = new VoucherMatchLpaActorHelper();
        $name_dob_matches = [];
        $name_matches = [];
        $addressMatch = false;
        foreach ($lpasData as $lpa) {
            $name_dob_matches = array_merge($name_matches, $matchHelper->checkMatch(
                $lpa,
                $detailsData["firstName"],
                $detailsData["lastName"],
                $detailsData["dob"],
            ));
            $name_matches = array_merge($name_matches, $matchHelper->checkMatch(
                $lpa,
                $detailsData["firstName"],
                $detailsData["lastName"],
            ));
            $addressMatch = $addressMatch || $matchHelper->checkAddressDonorMatch(
                $lpa,
                $detailsData["address"],
            );
        }

        foreach ($name_dob_matches as $match) {
            if ($match["type"] === LpaActorTypes::DONOR->value) {
                $response["error"] = true;
                $response["warning"] = 'donor-match';
                $matchName = implode(" ", [$match["firstName"], $match["lastName"]]);
                $response["message"] = "The person vouching cannot have the same name and date of birth as the donor.";
            }
        }
        if ($addressMatch and ! $response["error"]) {
            $response["error"] = true;
            $response["warning"] = "donor-address-match";
            $response["message"]= 'The person vouching cannot have the same address as the donor.';
        }
        if (! isset($response["warning"])) {
            foreach ($name_matches as $match) {
                if ($match["type"] == LpaActorTypes::CP->value) {

                    $matchName = implode(" ", [$match["firstName"], $match["lastName"]]);

                    $response["warning"] = $match["type"] . '-match';
                    $response["matchName"] = $matchName;
                    $response["message"] = "There is a certificate provider called " . $matchName . " named on this LPA. A certificate provider cannot vouch for the identity of a donor. Confirm that these are two different people with the same name.";
                    $response["additionalRow"] = [
                        "type" => "Certificate provider name",
                        "value" => $matchName
                    ];
                } elseif ($match["type"] == LpaActorTypes::ATTORNEY->value) {

                    $matchName = implode(" ", [$match["firstName"], $match["lastName"]]);

                    $response["warning"] = $match["type"] . '-match';
                    $response["matchName"] = $matchName;
                    $response["message"] = "There is an attorney called " . $matchName . " named on this LPA. An attorney cannot vouch for the identity of a donor. Confirm that these are two different people with the same name.";
                    $response["additionalRow"] = [
                        "type" => "Attorney name",
                        "value" => $matchName
                    ];
                } elseif ($match["type"] == LpaActorTypes::R_ATTORNEY->value) {

                    $matchName = implode(" ", [$match["firstName"], $match["lastName"]]);

                    $response["warning"] = $match["type"] . '-match';
                    $response["matchName"] = $matchName;
                    $response["message"] = "There is a replacement attorney called " . $matchName . " named on this LPA. A replacement attorney cannot vouch for the identity of a donor. Confirm that these are two different people with the same name.";
                    $response["additionalRow"] = [
                        "type" => "Replacement attorney name",
                        "value" => $matchName
                    ];
                }
            }
        }

        $lpas = [];
        foreach ($lpasData as $lpa) {
            $lpas[] = [
                "uId" => $lpa["uId"],
                "type" => LpaTypes::fromName( $lpa["opg.poas.sirius"]["caseSubtype"]),
            ];
        }

        $response["lpas"] = $lpas;
        $response["donorName"] = $this->getDonorNameFromSiriusResponse(current($lpasData));
        $response["donorDob"] = $this->getDonorDobFromSiriusResponse(current($lpasData));
        $response["donorAddress"] = $this->getDonorAddressFromSiriusResponse(current($lpasData));

        return $response;
    }
}

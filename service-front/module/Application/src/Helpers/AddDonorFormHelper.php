<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Enums\LpaTypes;
use Application\Enums\LpaActorTypes;
use Application\Helpers\AddressProcessorHelper;
// TODO: actually create and make use of
use Application\Helpers\DTO\AddDonorFormHelperResponseDto;
use Application\Helpers\VoucherMatchLpaActorHelper;
use AWS\CRT\HTTP\Response;
use Laminas\Form\FormInterface;
use DateTime;

class AddDonorFormHelper
{

    public function checkStatus(array $lpaData): array
    {
        // TODO: clarify which statuses are ok to be vouched for
        // and all the potential errors which could be shown
        $response = [
            "problem" => false,
            "status" => "",
            "message" => ""
        ];
        // if (
        //     array_key_exists('opg.poas.lpastore', $lpaData) &&
        //     array_key_exists('status', $lpaData['opg.poas.lpastore'])
        // ) {
        //     $status = $lpaData['opg.poas.lpastore']['status'];
        //     if ( in_array($status, ['complete', 'registered', 'in progress'])) {
        //         $response["problem"] = true;
        //         $response["message"] = "This LPA cannot be added as an ID" .
        //             " check has already been completed for this LPA.";
        //     }
        //     if ($response["status"] == 'draft') {
        //         $response["problem"] = true;
        //         $response["message"] = "This LPA cannot be added as it’s status is set to Draft.
        //             LPAs need to be in the In Progress status to be added to this ID check.";
        //     }
        // } else {
        //     $response["problem"] = true;
        //     $response["message"] = "No LPA Found.";
        // }
        return $response;
    }

    public static function getDonorNameFromSiriusResponse(array $lpaData): string
    {
        return implode(' ', [
            $lpaData['opg.poas.sirius']['donor']['firstname'] ?? '',
            $lpaData['opg.poas.sirius']['donor']['surname'] ?? '',
        ]);
    }

    public static function getDonorDobFromSiriusResponse(array $lpaData): string
    {
        // this is needed as PHP parses dates with mm/dd/yyyy by default
        $dob = implode("-", array_reverse(explode("/", $lpaData["opg.poas.sirius"]["donor"]["dob"])));
        return DateTime::createFromFormat('Y-m-d', $dob)->format('d M Y');
    }

    public static function getDonorAddressFromSiriusResponse(array $lpaData): array
    {
        return AddressProcessorHelper::processAddress($lpaData['opg.poas.sirius']['donor'], 'siriusAddressType');
    }

    public function checkLpa($lpa, $detailsData) {

        $response = [
            "problem" => false,
            "error" => false,
            "warning" => "",
            "message" => "",
            "additionalRows" => [],
        ];

        $matchHelper = new VoucherMatchLpaActorHelper();

        $match = $matchHelper->checkMatch(
            $lpa,
            $detailsData["firstName"],
            $detailsData["lastName"],
            $detailsData["dob"],
        );

        if ($match) {
            $matchName = implode(" ", [$match["firstName"], $match["lastName"]]);
            $response["error"] = true;

            $messageArticle = [
                LpaActorTypes::DONOR->value => "the",
                LpaActorTypes::CP->value => "a",
                LpaActorTypes::ATTORNEY->value => "an",
                LpaActorTypes::R_ATTORNEY->value => "a",
            ];

            $response["message"] = "The person vouching cannot have the same name and date of birth as " .
                "{$messageArticle[$match['type']]} {$match['type']}.";

            if ($match["type"] != LpaActorTypes::DONOR->value) {
                $response["warning"] = 'actor-match';
                $response["additionalRows"] = [
                    [
                        "type" => ucfirst($match['type']) . " name",
                        "value" => $matchName
                    ],
                    [
                        "type" => ucfirst($match['type']) . "date of birth",
                        "value" => DateTime::createFromFormat('Y-m-d', $match["dob"])->format('d M Y')
                    ]
                ];
            } else {
                $response["warning"] = 'donor-match';
            }
            return $response;
        }

        $addressMatch = $matchHelper->checkAddressDonorMatch($lpa, $detailsData["address"]);

        if ($addressMatch) {
            $response["error"] = true;
            $response["warning"] = "address-match";
            $response["message"] = "The person vouching cannot live at the same address as the donor.";

            return $response;
        }

        // we check certificate-provider separately as their dob is not recorded on the LPA so
        // a warning needs to be raised if there is a name match.
        $actor = [
            "firstName" => $lpa["opg.poas.lpastore"]["certificateProvider"]["firstNames"] ?? null,
            "lastName" => $lpa["opg.poas.lpastore"]["certificateProvider"]["lastName"] ?? null,
        ];
        $cp_name_match = $matchHelper->compareName(
            $detailsData["firstName"],
            $detailsData["lastName"],
            $actor
        );
        if ($cp_name_match) {

            $matchName = $actor["firstName"] . " " . $actor["lastName"];

            $response["warning"] = 'actor-match';
            $response["message"] = "There is a certificate provider called {$matchName} named on this LPA. A certificate provider cannot vouch for the identity of a donor. Confirm that these are two different people with the same name.";
            $response["additionalRows"] = [
                [
                    "type" => "Certificate provider name",
                    "value" => $matchName
                ]
            ];
        }
        return $response;
    }

    public function processLpas($lpasData, $detailsData) {

        // TODO: filter out LPAs which are already in $detailsData
        $response = [
            "lpasCount" => 0,
            "error" => false,
            "warning" => null,
            "message" => null,
            "additionalRow" => null,
        ];

        $lpas = [];
        foreach ($lpasData as $lpa) {
            $lpas[] = array_merge($this->checkStatus($lpa), [
                "uId" => $lpa["uId"],
                "type" => LpaTypes::fromName( $lpa["opg.poas.sirius"]["caseSubtype"])
            ]);
        }

        // if there is 1 LPA returned and there is a problem then we just flag the problem
        // if there are multiple LPAs and not all have a problem, then we remove the problem ones and continue (should we show some message)?
        // if there are multiple LPAs and they all have a problem then we flag the first...
        if (count($lpas) === 1) {
            $lpa = current($lpas);
            if ($lpa["problem"]) {
                $response["problem"] = true;
                $response["status"] = $lpa["status"];
                $response["message"] = $lpa["message"];

                return $response;
            }
        } else {
            $lpa = array_filter($lpas, function ($s) {
                return ! $s["problem"];
            });
            if (count($lpa) === 0) {
                $response["problem"] = true;
                $response["message"] = "The LPA cannot be added";

                return $response;
            }
        }

        $lpas = [];
        foreach ($lpasData as $lpa) {
            $lpas[] = array_merge($this->checkLpa($lpa, $detailsData), [
                "uId" => $lpa["uId"],
                "type" => LpaTypes::fromName( $lpa["opg.poas.sirius"]["caseSubtype"]),
            ]);
        }

        if (count($lpas) === 1) {
            $lpa = current($lpas);
            $response["error"] = $lpa["error"];
            $response["warning"] = $lpa["warning"];
            $response["message"] = $lpa["message"];
            $response["additionalRows"] = $lpa["additionalRows"];
        } else {
            $lpas = array_filter($lpas, function ($a) {
                return ! $a["error"];
            });
        }

        if (count($lpas) === 0 ) {
            // can we add a message to the form itself???
            // or could have a different kind of error (problem...??)
        } else {
            // theres not an error but we might need to show a warning
            $warnings = array_filter($lpas, function ($a) {
                return ! is_null($a["warning"]);
            });
            if ($warnings) {
                $response["warning"] = current($warnings)["warning"];
                $response["message"] = current($warnings)["message"];
                $response["additionalRows"] = current($warnings)["additionalRows"];
            }
        }

        $response["lpasCount"] = count($lpas);
        $response["lpas"] = $lpas;
        $response["donorName"] = $this->getDonorNameFromSiriusResponse(current($lpasData));
        $response["donorDob"] = $this->getDonorDobFromSiriusResponse(current($lpasData));
        $response["donorAddress"] = $this->getDonorAddressFromSiriusResponse(current($lpasData));

        return $response;
    }
}

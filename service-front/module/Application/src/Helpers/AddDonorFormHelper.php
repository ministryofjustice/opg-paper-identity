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

    public function __construct(
        private readonly VoucherMatchLpaActorHelper $matchHelper)
    {
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

    public function checkLpaStatus(array $lpaData): array
    {
        // TODO: clarify which statuses are ok to be vouched for
        // and all the potential errors which could be shown
        $response = [
            "problem" => false,
            "status" => "",
            "message" => ""
        ];
        if (
            array_key_exists('opg.poas.lpastore', $lpaData) &&
            array_key_exists('status', $lpaData['opg.poas.lpastore'])
        ) {
            $response['status'] = $lpaData['opg.poas.lpastore']['status'];
            if ( in_array($response['status'], ['complete', 'registered'])) {
                $response["problem"] = true;
                $response["message"] = "This LPA cannot be added as an ID" .
                    " check has already been completed for this LPA.";
            }
            if ($response["status"] == 'draft') {
                $response["problem"] = true;
                $response["message"] = "This LPA cannot be added as it’s status is set to Draft." .
                    " LPAs need to be in the In Progress status to be added to this ID check.";
            }
        } else {
            $response["problem"] = true;
            $response["message"] = "No LPA Found.";
        }
        return $response;
    }

    public function checkLpaIdMatch($lpa, $detailsData) {

        $response = [
            "problem" => false,
            "error" => false,
            "warning" => "",
            "message" => "",
            "additionalRows" => [],
        ];

        $match = $this->matchHelper->checkMatch(
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
                        "type" => ucfirst($match['type']) . " date of birth",
                        "value" => DateTime::createFromFormat('Y-m-d', $match["dob"])->format('d M Y')
                    ]
                ];
            } else {
                $response["warning"] = 'donor-match';
            }
            return $response;
        }

        $addressMatch = $this->matchHelper->checkAddressDonorMatch($lpa, $detailsData["address"]);

        if ($addressMatch) {
            $response["error"] = true;
            $response["warning"] = "address-match";
            $response["message"] = "The person vouching cannot live at the same address as the donor.";

            return $response;
        }

        // we check certificate-provider separately as their dob is not recorded on the LPA so
        // a warning needs to be raised if there is a name match instead.
        $actor = [
            "firstName" => $lpa["opg.poas.lpastore"]["certificateProvider"]["firstNames"] ?? null,
            "lastName" => $lpa["opg.poas.lpastore"]["certificateProvider"]["lastName"] ?? null,
        ];
        $cp_name_match = $this->matchHelper->compareName(
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

    public function processLpas(array $lpasData, array $detailsData) {

        $response = [
            "lpasCount" => 0,
            "problem" => false,
            "error" => false,
            "warning" => "",
            "message" => "",
            "additionalRows" => [],
        ];

        if (empty($lpasData)) {
            $response["problem"] = true;
            $response["message"] = "No LPA Found.";
            return $response;
        }

        $lpasData = array_filter($lpasData, function($lpa) use($detailsData) {
            return ! in_array($lpa["opg.poas.sirius"]["uId"], $detailsData["lpas"]);
        });
        if (empty($lpasData)) {
            $response["problem"] = true;
            $response["message"] = "This LPA has already been added to this identity check.";
            return $response;
        }

        $lpas = [];
        foreach ($lpasData as $lpa) {
            $result = [
                "uId" => $lpa["opg.poas.sirius"]["uId"],
                "type" => LpaTypes::fromName( $lpa["opg.poas.sirius"]["caseSubtype"]),
            ];
            $status = $this->checkLpaStatus($lpa);
            if ($status["problem"]) {
                $result = array_merge($result, $status);
            } else {
                $result = array_merge($result, $this->checkLpaIdMatch($lpa, $detailsData));
            }

            $lpas[] = $result;
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
        }
        // need to sort this out so we can show each of the errors???
        $lpas = array_filter($lpas, function ($s) {
            return ! $s["problem"];
        });
        if (empty($lpas)) {
            $response["problem"] = true;
            $response["message"] = "These LPAs cannot be added.";

            return $response;
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

            if (empty($lpas)) {
                $response["problem"] = true;
                $response["message"] = "These LPAs cannot be added, voucher details match with actors.";

                return $response;
            } else {
                // theres not an error but we might need to show a warning
                $warnings = array_filter($lpas, function ($a) {
                    return strlen($a["warning"]) > 0;
                });

                if ($warnings) {
                    $response["warning"] = current($warnings)["warning"];
                    $response["message"] = current($warnings)["message"];
                    $response["additionalRows"] = current($warnings)["additionalRows"];
                }
            }
        }

        $lpa = current($lpasData);

        $response["lpasCount"] = count($lpas);
        $response["lpas"] = $lpas;
        $response["donorName"] = $this->getDonorNameFromSiriusResponse($lpa);
        $response["donorDob"] = $this->getDonorDobFromSiriusResponse($lpa);
        $response["donorAddress"] = $this->getDonorAddressFromSiriusResponse($lpa);

        return $response;
    }
}

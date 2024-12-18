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


    private function checkLpa($lpa, $detailsData) {

        $error = false;
        $warning = null;
        $message = null;
        $additionalRows = [];

        $matchHelper = new VoucherMatchLpaActorHelper();

        $matches = $matchHelper->checkMatch(
            $lpa,
            $detailsData["firstName"],
            $detailsData["lastName"],
            $detailsData["dob"],
        );
        // we need this to not filter out everything if the dob on the LPA is null...

        echo(json_encode($lpa));
        echo(json_encode($matches));

        foreach ($matches as $match) {
            $matchName = implode(" ", [$match["firstName"], $match["lastName"]]);

            // if there is a dob in the match then we stop this LPA being added
            if (! is_null($match["dob"])) {
                $error = true;
                $message = "The person vouching cannot have the same name and date of birth as the {$match['type']}.";

                if ($match["type"] != LpaActorTypes::DONOR->value) {
                    $warning = 'actor-match';
                    $additionalRows = [
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
                    $warning = 'donor-match';
                }
                break;
            } else {
                if ($match["type"] == LpaActorTypes::DONOR->value) {
                    $warning = 'donor-match';
                }
                $warning = 'actor-match';
                if ($match["type"] == LpaActorTypes::ATTORNEY->value) {
                    $messageArticle = "an";
                    $messageArticleU = "An";
                } else {
                    $messageArticle = "a";
                    $messageArticleU = "A";
                }
                $message = "There is {$messageArticle} {$match['type']} called {$matchName} named on this LPA. {$messageArticleU} {$match['type']} cannot vouch for the identity of a donor. Confirm that these are two different people with the same name.";
            }
        }

        if (! $error) {
            $addressMatch = $matchHelper->checkAddressDonorMatch($lpa, $detailsData["address"]);

            if ($addressMatch) {
                $error = true;
                $warning = "address-match";
                $message = "The person vouching cannot live at the same address as the donor.";
            }
        }

        return [
            "error" => $error,
            "warning" => $warning,
            "message" => $message,
            "additionalRows" => $additionalRows,
        ];
    }


    public function processLpas($lpasData, $detailsData) {

        $response = [
            "lpasCount" => 0,
            "error" => false,
            "warning" => null,
            "message" => null,
            "additionalRow" => null,
        ];

        $lpas = [];
        foreach ($lpasData as $lpa) {
            $lpas[] = array_merge($this->checkLpa($lpa, $detailsData), [
                "uId" => $lpa["uId"],
                "type" => LpaTypes::fromName( $lpa["opg.poas.sirius"]["caseSubtype"]),
            ]);
        }

        echo(json_encode($lpas));

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
        }

        $response["lpasCount"] = count($lpas);
        $response["lpas"] = $lpas;
        $response["donorName"] = $this->getDonorNameFromSiriusResponse(current($lpasData));
        $response["donorDob"] = $this->getDonorDobFromSiriusResponse(current($lpasData));
        $response["donorAddress"] = $this->getDonorAddressFromSiriusResponse(current($lpasData));

        return $response;
    }
}

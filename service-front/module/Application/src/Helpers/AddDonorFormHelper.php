<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Enums\LpaTypes;
use Application\Enums\LpaActorTypes;
use Application\Helpers\AddressProcessorHelper;
use Application\Helpers\VoucherMatchLpaActorHelper;
use Application\Services\SiriusApiService;
use Application\Contracts\OpgApiServiceInterface;
use Application\Enums\LpaStatusType;
use DateTime;

/**
 * @psalm-import-type Lpa from SiriusApiService
 * @psalm-import-type Address from OpgApiServiceInterface
 * @psalm-import-type CaseData from OpgApiServiceInterface
 *
 * @psalm-type LpaStatus = array {
 *   problem: bool,
 *   message: string
 * }
 *
 * @psalm-type AdditionalRow = array {
 *   type: string,
 *   value: string
 * }
 *
 *  @psalm-type LpaIdMatchCheck = array {
 *   problem: bool,
 *   error: bool,
 *   warning: string,
 *   message: string,
 *   additionalRows: AdditionalRow[]
 * }
 *
 * @psalm-type CheckedLpa = array {
 *   uId: string,
 *   type: string,
 *   problem: bool,
 *   error: bool,
 *   warning: string,
 *   message: string,
 *   additionalRows: AdditionalRow[]
 * }
 *
 * @psalm-type ProcessedLpas = array {
 *   lpasCount: int,
 *   problem: bool,
 *   error: bool,
 *   warning: string,
 *   message: string,
 *   additionalRows: AdditionalRow[],
 *   lpas?: CheckedLpa[],
 *   donorName?: string,
 *   donorDob?: string,
 *   donorAddress?: Address
 * }
 * }
 */
class AddDonorFormHelper
{
    public function __construct(
        private readonly VoucherMatchLpaActorHelper $matchHelper
    ) {
    }

    private static function formatDate(
        string $date,
        string $inputFormat = 'Y-m-d',
        string $outputFormat = 'd M Y'
    ): string {
        $formattedDate = DateTime::createFromFormat($inputFormat, $date);
        if (! $formattedDate) {
            return '';
        }
        return $formattedDate->format($outputFormat);
    }

    /**
     * @param Lpa $lpaData
     * @return string
     */
    public static function getDonorNameFromSiriusResponse(array $lpaData): string
    {
        return implode(' ', [
            $lpaData['opg.poas.sirius']['donor']['firstname'] ?? '',
            $lpaData['opg.poas.sirius']['donor']['surname'] ?? '',
        ]);
    }

    /**
     * @param Lpa $lpaData
     * @return string
     */
    public static function getDonorDobFromSiriusResponse(array $lpaData): string
    {
        $dob = $lpaData["opg.poas.sirius"]["donor"]["dob"];
        return self::formatDate($dob, 'd/m/Y', 'd M Y');
    }

    /**
     * @param Lpa $lpaData
     * @return Address
     */
    public static function getDonorAddressFromSiriusResponse(array $lpaData): array
    {
        return AddressProcessorHelper::processAddress($lpaData['opg.poas.sirius']['donor'], 'siriusAddressType');
    }

    /**
     * @param Lpa $lpaData
     * @return LpaStatus
     */
    public function checkLpaStatus(array $lpaData): array
    {
        $response = [
            "problem" => false,
            "message" => ""
        ];

        if (
            array_key_exists('opg.poas.lpastore', $lpaData) &&
            ! is_null($lpaData['opg.poas.lpastore']) &&
            array_key_exists('status', $lpaData['opg.poas.lpastore'])
        ) {
            $status = LpaStatusType::from($lpaData['opg.poas.lpastore']['status']);
            if ($status == LpaStatusType::Registered) {
                $response["problem"] = true;
                $response["message"] = "This LPA cannot be added as an ID" .
                    " check has already been completed for this LPA.";
            }
            if ($status == LpaStatusType::Draft) {
                $response["problem"] = true;
                $response["message"] = "This LPA cannot be added as it’s status is set to \"Draft\"." .
                    " LPAs need to be in the \"In progress\" status to be added to this ID check.";
            }
        } else {
            $response["problem"] = true;
            $response["message"] = "No LPA Found.";
        }
        return $response;
    }

    /**
     * @param Lpa $lpa
     * @param CaseData $detailsData
     * @return LpaIdMatchCheck
     */
    public function checkLpaIdMatch(array $lpa, array $detailsData): array
    {

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
                        "value" => self::formatDate($match['dob'])
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
            $response["message"] = "There is a certificate provider called {$matchName} named on this LPA. " .
                'A certificate provider cannot vouch for the identity of a donor. ' .
                'Confirm that these are two different people with the same name.';
            $response["additionalRows"] = [
                [
                    "type" => "Certificate provider name",
                    "value" => $matchName
                ]
            ];
        }
        return $response;
    }

    /**
     * @param Lpa[] $lpasData
     * @param CaseData $detailsData
     * @return CheckedLpa[]
     */
    private function checkLpas(array $lpasData, array $detailsData): array
    {
        $baseResponse = [
            'uId' => '',
            'type' => '',
            'problem' => false,
            'error' => false,
            'warning' => '',
            'message' => '',
            'additionalRows' => [],
        ];
        $lpas = [];
        foreach ($lpasData as $lpa) {
            $result = [
                "uId" => $lpa["opg.poas.sirius"]["uId"],
                "type" => LpaTypes::fromName($lpa["opg.poas.sirius"]["caseSubtype"]),
                "error" => false,
            ];
            $status = $this->checkLpaStatus($lpa);
            if ($status["problem"]) {
                $result = array_merge($result, $status);
            } else {
                $result = array_merge($result, $this->checkLpaIdMatch($lpa, $detailsData));
            }

            $lpas[] = array_merge($baseResponse, $result);
        }
        return $lpas;
    }

    /**
     * @param Lpa[] $lpasData
     * @param CaseData $detailsData
     * @return ProcessedLpas
     */
    public function processLpas(array $lpasData, array $detailsData): array
    {
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

        $lpasData = array_filter($lpasData, function ($lpa) use ($detailsData) {
            return ! in_array($lpa["opg.poas.sirius"]["uId"], $detailsData["lpas"]);
        });
        if (empty($lpasData)) {
            $response["problem"] = true;
            $response["message"] = "This LPA has already been added to this identity check.";
            return $response;
        }

        $lpas = $this->checkLpas($lpasData, $detailsData);

        // if there is 1 LPA returned and there is a problem then we just flag the problem
        if (count($lpas) === 1 && current($lpas)["problem"]) {
            $response["problem"] = true;
            $response["message"] = current($lpas)["message"];

            return $response;
        }

        // if there are multiple LPAs then we remove the problem ones and continue
        $lpas = array_filter($lpas, function ($s) {
            return ! $s["problem"];
        });
        if (empty($lpas)) {
            $response["problem"] = true;
            $response["message"] =
                "These LPAs cannot be added as they do not have the correct status for an ID check. " .
                "LPAs need to be in the \"In progress\" status to be added to this identity check.";
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
                    // we only show the first warning
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

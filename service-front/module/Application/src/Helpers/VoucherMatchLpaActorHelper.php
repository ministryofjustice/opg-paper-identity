<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Enums\LpaActorTypes;
use Laminas\Http\Header\RetryAfter;

/**
 * @psalm-import-type Lpa from SiriusApiService
 */
class VoucherMatchLpaActorHelper
{
    /**
     * @param Lpa $lpasData
    */
    private function getLpaActors(array $lpasData): array
    {
        $actors = [];

        if (key_exists("opg.poas.lpastore", $lpasData)) {

            $actors[] = [
                "firstName" => $lpasData["opg.poas.lpastore"]["donor"]["firstNames"],
                "lastName" => $lpasData["opg.poas.lpastore"]["donor"]["lastName"],
                "dob" => $lpasData["opg.poas.lpastore"]["donor"]["dateOfBirth"],
                "type" => LpaActorTypes::DONOR->value,
            ];
            $actors[] = [
                "firstName" => $lpasData["opg.poas.lpastore"]["certificateProvider"]["firstNames"],
                "lastName" => $lpasData["opg.poas.lpastore"]["certificateProvider"]["lastName"],
                "dob" => $lpasData["opg.poas.lpastore"]["certificateProvider"]["dateOfBirth"],
                "type" => LpaActorTypes::CP->value,
            ];

            foreach ($lpasData["opg.poas.lpastore"]["attorneys"] as $attorney) {
                if (in_array($attorney["status"], ["active", "removed"])) {
                    $actors[] = [
                        "firstName" => $attorney["firstNames"],
                        "lastName" => $attorney["lastName"],
                        "dob" => $attorney["dateOfBirth"],
                        "type" => LpaActorTypes::ATTORNEY->value,
                    ];
                } elseif ($attorney["status"] === "replacement") {
                    $actors[] = [
                        "firstName" => $attorney["firstNames"],
                        "lastName" => $attorney["lastName"],
                        "dob" => $attorney["dateOfBirth"],
                        "type" => LpaActorTypes::R_ATTORNEY->value,
                    ];
                }
            }
        }
        if (key_exists("opg.poas.sirius", $lpasData)) {
            $actors[] = [
                "firstName" => $lpasData["opg.poas.sirius"]["firstname"],
                "lastName" => $lpasData["opg.poas.sirius"]["surname"],
                "dob" => $lpasData["opg.poas.sirius"]["dob"],
                "type" => LpaActorTypes::DONOR->value,
            ];
        }

        return $actors;
    }

    private function compareName(string $firstName, string $lastName, array $actor): bool
    {
        if (is_null($firstName) || is_null($lastName) || is_null($actor["firstName"]) || is_null($actor["lastName"])) {
            return false;
        }

        $firstNameMatch = strtolower(trim($firstName)) === strtolower(trim($actor["firstName"]));
        $lastNameMatch = strtolower(trim($lastName)) === strtolower(trim($actor["lastName"]));

        return $firstNameMatch && $lastNameMatch;
    }

    private function compareDob(?string $dob, array $actor): bool
    {
        if (is_null($dob) || is_null($actor["dob"])) {
            return false;
        }

        return strtolower(trim($dob)) === strtolower(trim($actor["dob"]));
    }

    /**
     * @param Lpa $lpasData
     * @param string $firstName
     * @param string $lastName
     * @param string $dob
    */
    public function checkMatch(array $lpasData, ?string $firstName, ?string $lastName, ?string $dob = null): array
    {
        $actors = $this->getLpaActors($lpasData);

        $matches = array_filter($actors, function($a) use ($firstName, $lastName) {
            return $this->compareName($firstName, $lastName, $a);
        });

        // if dob is not given we only check against name
        if ($dob) {
            $matches = array_filter($matches, function($a) use ($dob) {
                return $this->compareDob($dob, $a);
            });
        }
        return $matches;
    }
}

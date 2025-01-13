<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Enums\LpaActorTypes;
use Application\Helpers\AddressProcessorHelper;
use Application\Services\SiriusApiService;

/**
 * @psalm-import-type Lpa from SiriusApiService
 *
 * @psalm-type Actor = array{
 *   "firstName": string,
 *   "lastName": string,
 *   "dob": string,
 *   "type": string,
 * }
 */
class VoucherMatchLpaActorHelper
{
    private function getLpaActors(array $lpasData): array
    {
        $actors = [];
        if (! empty($lpasData["opg.poas.lpastore"])) {
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
                if ($attorney["appointmentType"] === "replacement") {
                    $actors[] = [
                        "firstName" => $attorney["firstNames"],
                        "lastName" => $attorney["lastName"],
                        "dob" => $attorney["dateOfBirth"],
                        "type" => LpaActorTypes::R_ATTORNEY->value,
                    ];
                } else {
                    $actors[] = [
                        "firstName" => $attorney["firstNames"],
                        "lastName" => $attorney["lastName"],
                        "dob" => $attorney["dateOfBirth"],
                        "type" => LpaActorTypes::ATTORNEY->value,
                    ];
                }
            }
        } elseif (! empty($lpasData["opg.poas.sirius"])) {
            $actors[] = [
                "firstName" => $lpasData["opg.poas.sirius"]["donor"]["firstname"],
                "lastName" => $lpasData["opg.poas.sirius"]["donor"]["surname"],
                // we replace
                "dob" => implode("-", array_reverse(explode("/", $lpasData["opg.poas.sirius"]["donor"]["dob"]))),
                "type" => LpaActorTypes::DONOR->value,
            ];
        }

        return $actors;
    }

    private function compareName(string $firstName, string $lastName, array $actor): bool
    {
        if (is_null($actor["firstName"]) || is_null($actor["lastName"])) {
            return false;
        }

        $firstNameMatch = strtolower(trim($firstName)) === strtolower(trim($actor["firstName"]));
        $lastNameMatch = strtolower(trim($lastName)) === strtolower(trim($actor["lastName"]));

        return $firstNameMatch && $lastNameMatch;
    }

    private function compareDob(string $dob, array $actor): bool
    {
        if (is_null($actor["dob"])) {
            return false;
        }

        return date_parse($dob) === date_parse($actor["dob"]);
    }

    /**
     * @param Lpa $lpasData
     * @param string $firstName
     * @param string $lastName
     * @param string $dob
     * @return Actor[]
    */
    public function checkMatch(array $lpasData, string $firstName, string $lastName, string $dob = null): array
    {
        $actors = $this->getLpaActors($lpasData);

        $matches = array_filter($actors, function ($a) use ($firstName, $lastName) {
            return $this->compareName($firstName, $lastName, $a);
        });

        // if dob is not given we only check against name
        if (! is_null($dob)) {
            $matches = array_filter($matches, function ($a) use ($dob) {
                return $this->compareDob($dob, $a);
            });
        }
        return $matches;
    }

    public function checkAddressDonorMatch(array $lpasData, array $address): bool
    {
        if (! empty($lpasData["opg.poas.lpastore"]["donor"])) {
            $donorAddress = AddressProcessorHelper::processAddress(
                $lpasData["opg.poas.lpastore"]["donor"]["address"],
                'lpaStoreAddressType'
            );
        } elseif (! empty($lpasData["opg.poas.sirius"])) {
            $donorAddress = AddressProcessorHelper::processAddress(
                $lpasData["opg.poas.sirius"]["donor"],
                'siriusAddressType'
            );
        }

        if (! isset($donorAddress)) {
            return false;
        }

        // we check address match on line1 and postcode only, ignoring case (and whitespace in postcode)
        /**
        * @psalm-suppress PossiblyInvalidArgument
        */
        return strtolower(trim($donorAddress["line1"])) === strtolower(trim($address["line1"])) &&
                strtolower(preg_replace("/\s+/", "", $donorAddress["postcode"])) ===
                strtolower(preg_replace("/\s+/", "", $address["postcode"]));
    }
}

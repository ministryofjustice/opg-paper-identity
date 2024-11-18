<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Enums\LpaActorTypes;

class VoucherMatchLpaActorHelper
{
    private function compareName(
        ?string $firstNameOne,
        ?string $lastNameOne,
        ?string $firstNameTwo,
        ?string $lastNameTwo
    ): bool {
        // surely there is a more elegant way to do this??
        if (is_null($firstNameOne) || is_null($lastNameOne) || is_null($firstNameTwo) || is_null($lastNameTwo)) {
            return false;
        }

        $firstNameMatch = strtolower(trim($firstNameOne)) === strtolower(trim($firstNameTwo));
        $lastNameMatch = strtolower(trim($lastNameOne)) === strtolower(trim($lastNameTwo));

        return $firstNameMatch && $lastNameMatch;
    }

    public function checkNameMatch(?string $firstName, ?string $lastName, array $lpasData): array
    {
        $matches = [
            LpaActorTypes::DONOR->value => false,
            LpaActorTypes::CP->value => false,
            LpaActorTypes::ATTORNEY->value => false,
            LpaActorTypes::R_ATTORNEY->value => false
        ];

        if (key_exists("opg.poas.lpastore", $lpasData)) {
            $matches[LpaActorTypes::DONOR->value] = $this->compareName(
                $firstName,
                $lastName,
                $lpasData["opg.poas.lpastore"]["donor"]["firstNames"] ?? null,
                $lpasData["opg.poas.lpastore"]["donor"]["lastName"] ?? null,
            );

            $matches[LpaActorTypes::CP->value] = $this->compareName(
                $firstName,
                $lastName,
                $lpasData["opg.poas.lpastore"]["certificateProvider"]["firstNames"] ?? null,
                $lpasData["opg.poas.lpastore"]["certificateProvider"]["lastName"] ?? null,
            );

            foreach ($lpasData["opg.poas.lpastore"]["attorneys"] ?? [] as $attorney) {
                if (
                    in_array($attorney["status"], ["active", "removed"]) &&
                    ! $matches[LpaActorTypes::ATTORNEY->value]
                ) {
                    $matches[LpaActorTypes::ATTORNEY->value] = $this->compareName(
                        $firstName,
                        $lastName,
                        $attorney["firstNames"] ?? null,
                        $attorney["lastName"] ?? null,
                    );
                }
                if ($attorney["status"] === "replacement" && ! $matches[LpaActorTypes::R_ATTORNEY->value]) {
                    $matches[LpaActorTypes::R_ATTORNEY->value] = $this->compareName(
                        $firstName,
                        $lastName,
                        $attorney["firstNames"] ?? null,
                        $attorney["lastName"] ?? null,
                    );
                }
            }
        } elseif (key_exists("opg.poas.sirius", $lpasData)) {
            $matches[LpaActorTypes::DONOR->value] = $this->compareName(
                $firstName,
                $lastName,
                $lpasData["opg.poas.sirius"]["donor"]["firstname"] ?? null,
                $lpasData["opg.poas.sirius"]["donor"]["surname"] ?? null,
            );
        }

        return array_keys($matches, true);
    }
}

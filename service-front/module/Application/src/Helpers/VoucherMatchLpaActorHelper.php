<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Enums\LpaActorTypes;

class VoucherMatchLpaActorHelper
{
    public function __construct()
    {
    }

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
        $matches = [];

        if (key_exists("opg.poas.lpastore", $lpasData)) {
            $matches[] = $this->compareName(
                $firstName,
                $lastName,
                $lpasData["opg.poas.lpastore"]["donor"]["firstNames"] ?? null,
                $lpasData["opg.poas.lpastore"]["donor"]["lastName"] ?? null,
            ) ? LpaActorTypes::DONOR->value : null;

            $matches[] = $this->compareName(
                $firstName,
                $lastName,
                $lpasData["opg.poas.lpastore"]["certificateProvider"]["firstNames"] ?? null,
                $lpasData["opg.poas.lpastore"]["certificateProvider"]["lastName"] ?? null,
            ) ? LpaActorTypes::CP->value : null;

            foreach ($lpasData["opg.poas.lpastore"]["attorneys"] ?? [] as $attorney) {
                if (in_array($attorney["status"], ["active", "removed"])) {
                    $matches[] = $this->compareName(
                        $firstName,
                        $lastName,
                        $attorney["firstNames"] ?? null,
                        $attorney["lastName"] ?? null,
                    ) ? LpaActorTypes::ATTORNEY->value : null;
                }
                if ($attorney["status"] === "replacement") {
                    $matches[] = $this->compareName(
                        $firstName,
                        $lastName,
                        $attorney["firstNames"] ?? null,
                        $attorney["lastName"] ?? null,
                    ) ? LpaActorTypes::R_ATTORNEY->value : null;
                }
            }
        } elseif (key_exists("opg.poas.sirius", $lpasData)) {
            $matches[] = $this->compareName(
                $firstName,
                $lastName,
                $lpasData["opg.poas.sirius"]["donor"]["firstname"] ?? null,
                $lpasData["opg.poas.sirius"]["donor"]["surname"] ?? null,
            ) ? LpaActorTypes::DONOR->value : null;
        }

        return array_values(array_filter($matches));
    }
}

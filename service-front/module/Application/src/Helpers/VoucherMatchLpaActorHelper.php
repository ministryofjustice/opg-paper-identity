<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Enums\LpaActorTypes;

class VoucherMatchLpaActorHelper
{
    public function __construct()
    {
    }

    private function compareName($firstNameOne, $lastNameOne, $firstNameTwo, $lastNameTwo) {

        $firstNameMatch = strtolower(trim($firstNameOne)) === strtolower(trim($firstNameTwo));
        $lastNameMatch = strtolower(trim($lastNameOne)) === strtolower(trim($lastNameTwo));

        return $firstNameMatch && $lastNameMatch;
    }

    public function checkNameMatch($firstName, $lastName, $lpasData) {

        $matches = [];
        if (key_exists("opg.poas.lpastore", $lpasData)){
            if (key_exists("donor", $lpasData["opg.poas.lpastore"])){

            }
        } elseif (key_exists("opg.poas.sirius", $lpasData)) {

        }
        return $matches;
    }
}
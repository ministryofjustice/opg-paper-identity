<?php

declare(strict_types=1);

namespace Application\Enums;

enum LpaTypes: string
{
    case PW = "personal-welfare";
    case PA = "property-and-affairs";

    public static function fromName(string $lpaTypeDescription): string
    {
        foreach (self::cases() as $type) {
            if ($lpaTypeDescription == $type->value) {
                return $type->name;
            }
        }
        return "";
    }
}

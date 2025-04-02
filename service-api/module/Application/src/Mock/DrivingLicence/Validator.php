<?php

declare(strict_types=1);

namespace Application\Mock\DrivingLicence;

use Application\DrivingLicence\ValidatorInterface;

class Validator implements ValidatorInterface
{
    public function validateDrivingLicence(string $licence): string
    {
        if (str_ends_with($licence, '8')) {
            return self::NO_MATCH;
        } elseif (str_ends_with($licence, '9')) {
            return self::NOT_ENOUGH_DETAILS;
        } else {
            return self::PASS;
        }
    }
}

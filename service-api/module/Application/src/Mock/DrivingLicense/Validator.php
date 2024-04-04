<?php

declare(strict_types=1);

namespace Application\Mock\DrivingLicense;

use Application\DrivingLicense\ValidatorInterface;

class Validator implements ValidatorInterface
{
    public function validateDrivingLicense(string $license): string
    {
        if (str_ends_with($license, '8')) {
            return self::NO_MATCH;
        } elseif (str_ends_with($license, '9')) {
            return self::NOT_ENOUGH_DETAILS;
        } else {
            return self::PASS;
        }
    }
}

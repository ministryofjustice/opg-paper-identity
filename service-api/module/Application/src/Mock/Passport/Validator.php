<?php

declare(strict_types=1);

namespace Application\Mock\Passport;

use Application\Passport\ValidatorInterface;

class Validator implements ValidatorInterface
{
    public function validatePassport(int $passportNo): string
    {
        $passportStr = (string)$passportNo;

        if (str_ends_with($passportStr, '8')) {
            return self::NO_MATCH;
        } elseif (str_ends_with($passportStr, '9')) {
            return self::NOT_ENOUGH_DETAILS;
        } else {
            return self::PASS;
        }
    }
}

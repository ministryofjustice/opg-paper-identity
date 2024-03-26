<?php

declare(strict_types=1);

namespace Application\Mock\Nino;

use Application\Nino\ValidatorInterface;

class Validator implements ValidatorInterface
{
    public function validateNINO(string $nino): string
    {
        if (str_ends_with($nino, 'C')) {
            return self::NO_MATCH;
        } elseif (str_ends_with($nino, 'D')) {
            return self::NOT_ENOUGH_DETAILS;
        } else {
            return self::PASS;
        }
    }
}

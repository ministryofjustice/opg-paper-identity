<?php

declare(strict_types=1);

namespace Application\Services;

use Application\Services\Contract\NINOServiceInterface;

class MockNinoService implements NINOServiceInterface
{
    public const NO_MATCH = 'Unable to verify NINO details';
    public const NOT_ENOUGH_DETAILS = 'Not enough details to continue with this form of identification';
    public const PASS = 'NINO check complete';
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

<?php

declare(strict_types=1);

namespace Application\Nino;

interface ValidatorInterface
{
    public const NO_MATCH = 'Unable to verify NINO details';
    public const NOT_ENOUGH_DETAILS = 'Not enough details to continue with this form of identification';
    public const PASS = 'NINO check complete';

    public function validateNINO(string $nino): string;
}

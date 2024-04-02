<?php

declare(strict_types=1);

namespace Application\Passport;

interface ValidatorInterface
{
    public const NO_MATCH = 'Unable to verify passport details';
    public const NOT_ENOUGH_DETAILS = 'Not enough details to continue with this form of identification';
    public const PASS = 'Passport check complete';

    public function validatePassport(int $passportNo): string;
}

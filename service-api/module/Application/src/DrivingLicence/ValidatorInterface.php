<?php

declare(strict_types=1);

namespace Application\DrivingLicence;

interface ValidatorInterface
{
    public const NO_MATCH = 'NO_MATCH';
    public const NOT_ENOUGH_DETAILS = 'NOT_ENOUGH_DETAILS';
    public const PASS = 'PASS';

    public function validateDrivingLicence(string $licence): string;
}

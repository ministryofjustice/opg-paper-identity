<?php

declare(strict_types=1);

namespace Application\DrivingLicense;

interface ValidatorInterface
{
    public const NO_MATCH = 'NO_MATCH';
    public const NOT_ENOUGH_DETAILS = 'NOT_ENOUGH_DETAILS';
    public const PASS = 'PASS';

    public function validateDrivingLicense(string $license): string;
}

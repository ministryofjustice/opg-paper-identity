<?php

declare(strict_types=1);

namespace Application\DrivingLicence;

use Application\DrivingLicence\ValidatorInterface;
use GuzzleHttp\Client;

/**
 * @psalm-suppress PossiblyUnusedProperty
 * Suppress unused $client pending implementation
 */
class Validator implements ValidatorInterface
{
    public function __construct(
        public readonly Client $client
    ) {
    }

    public function validateDrivingLicence(string $licence): string
    {
        return self::PASS;
    }
}

<?php

declare(strict_types=1);

namespace Application\DrivingLicense;

use Application\DrivingLicense\ValidatorInterface;
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

    public function validateDrivingLicense(string $license): string
    {
        return self::PASS;
    }
}

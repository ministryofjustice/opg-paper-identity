<?php

declare(strict_types=1);

namespace Application\Nino;

use GuzzleHttp\Client;
use Application\Nino\ValidatorInterface;

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
    //@TODO implement when we have API access
    public function validateNINO(string $nino): string
    {
        return self::PASS;
    }
}

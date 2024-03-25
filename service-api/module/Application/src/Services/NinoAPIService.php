<?php

declare(strict_types=1);

namespace Application\Services;

use GuzzleHttp\Client;
use Application\Services\Contract\NINOServiceInterface;

/**
 * @psalm-suppress PossiblyUnusedProperty
 * Suppress unused $client pending implementation
 */
class NinoAPIService implements NINOServiceInterface
{
    public function __construct(
        public readonly Client $client
    ) {
    }
    //@TODO implement when we have API access
    public function validateNINO(string $nino): string
    {
        return 'Pass';
    }
}

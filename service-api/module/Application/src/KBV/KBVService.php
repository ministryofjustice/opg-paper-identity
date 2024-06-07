<?php

declare(strict_types=1);

namespace Application\KBV;

use Application\KBV\KBVServiceInterface;
use GuzzleHttp\Client;

/**
 * @psalm-suppress PossiblyUnusedProperty
 * Suppress unused $client pending implementation
 */
class KBVService implements KBVServiceInterface
{
    public function __construct(
        public readonly Client $client
    ) {
    }

    public function fetchFormattedQuestions(string $uuid): array
    {
        // TODO: Implement fetchFormattedQuestions() method.
        return [];
    }
}

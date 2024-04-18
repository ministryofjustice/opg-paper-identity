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

    public function getKBVQuestions(string $uuid): array
    {
        // TODO: Implement getKBVQuestions() method on api access.
        return [];
    }
}

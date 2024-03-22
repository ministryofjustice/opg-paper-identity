<?php

declare(strict_types=1);

namespace Application\Services;

use GuzzleHttp\Client;
use Application\Services\Contract\NINOServiceInterface;

class NinoAPIService implements NINOServiceInterface
{
    public function __construct(
        public readonly Client $client
    ) {
    }

    public function validateNINO(string $nino): string
    {
        return 'Pass';
    }
}

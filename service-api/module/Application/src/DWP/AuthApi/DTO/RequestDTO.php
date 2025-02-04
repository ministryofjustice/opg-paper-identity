<?php

declare(strict_types=1);

namespace Application\DWP\AuthApi\DTO;

class RequestDTO
{
    public function __construct(
        public readonly string $grantType,
        public readonly string $clientId,
        public readonly string $clientSecret,
    ) {
    }

    public function toArray(): array
    {
        return [
            'grant_type' => $this->grantType,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ];
    }
}

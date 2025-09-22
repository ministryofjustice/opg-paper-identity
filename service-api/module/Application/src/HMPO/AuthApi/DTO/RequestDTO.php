<?php

declare(strict_types=1);

namespace Application\HMPO\AuthApi\DTO;

class RequestDTO
{
    public function __construct(
        public readonly string $grantType,
        public readonly string $clientId,
        public readonly string $secret,
    ) {
    }

    public function toArray(): array
    {
        return [
            'grantType' => $this->grantType,
            'clientId' => $this->clientId,
            'secret' => $this->secret,
        ];
    }
}

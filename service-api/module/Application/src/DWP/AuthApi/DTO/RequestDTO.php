<?php

declare(strict_types=1);

namespace Application\DWP\AuthApi\DTO;

class RequestDTO
{
    public function __construct(
        public readonly string $clientId,
        public readonly string $clientSecret,
        public readonly string $bundle,
        public readonly string $privateKey,
    ) {
    }

    public function toArray(): array
    {
        return [
            'clientId' => $this->clientId,
            'clientSecret' => $this->clientSecret,
            'bundle' => $this->bundle,
            'private_key' => $this->privateKey
        ];
    }
}

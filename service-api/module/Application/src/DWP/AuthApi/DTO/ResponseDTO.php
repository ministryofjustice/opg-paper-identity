<?php

declare(strict_types=1);

namespace Application\DWP\AuthApi\DTO;

class ResponseDTO
{
    public function __construct(
        public readonly string $accessToken,
        public readonly string $expiresIn,
        public readonly string $tokenType,
    ) {
    }

    public function accessToken(): string
    {
        return $this->accessToken;
    }

    public function expiresIn(): string
    {
        return $this->expiresIn;
    }

    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'expires_in' => $this->expiresIn,
            'token_type' => $this->tokenType,
        ];
    }
}

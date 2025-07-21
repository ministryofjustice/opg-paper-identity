<?php

declare(strict_types=1);

namespace Application\HMPO\AuthApi\DTO;

class ResponseDTO
{
    public function __construct(
        public readonly string $accessToken,
        public readonly int $expiresIn,
        public readonly int $refreshExpiresIn,
        public readonly ?string $refreshToken,
        public readonly string $tokenType,
    ) {
    }

    public function accessToken(): string
    {
        return $this->accessToken;
    }

    public function expiresIn(): int
    {
        return $this->expiresIn;
    }

    public function refreshExpiresIn(): int
    {
        return $this->refreshExpiresIn;
    }

    public function refreshToken(): string
    {
        return $this->refreshToken;
    }

    public function tokenType(): string
    {
        return $this->tokenType;
    }

    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'expires_in' => $this->expiresIn,
            'refresh_expires_in' => $this->refreshExpiresIn,
            'refresh_token' => $this->refreshToken,
            'token_type' => $this->tokenType,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Application\Services\Experian\FraudApi\DTO;

class ExperianCrosscoreFraudResponseDTO
{
    public function __construct(
        private readonly string $accessToken,
        private readonly string $refreshToken,
        private readonly string $issuedAt,
        private readonly string $expiresIn,
        private readonly string $tokenType,
    ) {
    }

    public function accessToken(): string
    {
        return $this->accessToken;
    }

    public function refreshToken(): string
    {
        return $this->refreshToken;
    }

    public function issuedAt(): string
    {
        return $this->issuedAt;
    }

    public function expiresIn(): string
    {
        return $this->expiresIn;
    }

    public function tokenType(): string
    {
        return $this->tokenType;
    }

    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'issued_at' => $this->issuedAt,
            'expires_in' => $this->expiresIn,
            'token_type' => $this->tokenType,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Application\Services\DTO;

class ExperianCrosscoreAuthResponseDTO
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
            $this->accessToken,
            $this->refreshToken,
            $this->issuedAt,
            $this->expiresIn,
            $this->tokenType,
        ];
    }
}

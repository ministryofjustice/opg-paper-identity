<?php

declare(strict_types=1);

namespace Application\Services\Experian\AuthApi\DTO;

class ExperianCrosscoreRefreshRequestDTO
{
    public function __construct(
        private readonly string $refreshToken,
        private readonly string $grantType,
        private readonly string $clientId,
        private readonly string $clientSecret
    ) {
    }

    public function refreshToken(): string
    {
        return $this->refreshToken;
    }

    public function grantType(): string
    {
        return $this->grantType;
    }

    public function clientId(): string
    {
        return $this->clientId;
    }

    public function clientSecret(): string
    {
        return $this->clientSecret;
    }

    public function toArray(): array
    {
        return [
            $this->refreshToken,
            $this->grantType,
            $this->clientId,
            $this->clientSecret,
        ];
    }
}

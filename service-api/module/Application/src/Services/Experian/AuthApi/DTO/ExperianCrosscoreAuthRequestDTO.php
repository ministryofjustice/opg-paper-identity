<?php

declare(strict_types=1);

namespace Application\Services\Experian\AuthApi\DTO;

class ExperianCrosscoreAuthRequestDTO
{
    public function __construct(
        private readonly string $userName,
        private readonly string $password,
        private readonly string $clientId,
        private readonly string $clientSecret
    ) {
    }

    public function userName(): string
    {
        return $this->userName;
    }

    public function password(): string
    {
        return $this->password;
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
            $this->userName,
            $this->password,
            $this->clientId,
            $this->clientSecret,
        ];
    }
}

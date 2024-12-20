<?php

declare(strict_types=1);

namespace Application\DWP\AuthApi\DTO;

class ResponseDTO
{
    public function __construct(
        private readonly string $accessToken,
        private readonly string $expiresIn,
        private readonly string $tokenType,
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

    /**
     * @psalm-suppress PossiblyUnusedMethod
     * @return string
     */
    public function tokenType(): string
    {
        return $this->tokenType;
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

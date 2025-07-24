<?php

declare(strict_types=1);

namespace Application\HMPO\AuthApi\DTO;

use Application\Services\Auth\DTO\ResponseDTO;

class HmpoResponseDTO extends ResponseDTO
{
    public string $accessToken;
    public int $expiresIn;
    public int $refreshExpiresIn;
    public ?string $refreshToken;
    public string $tokenType;

    public function __construct(array $responseArray) {
        $this->accessToken = $responseArray['access_token'];
        $this->expiresIn = $responseArray['expires_in'];
        $this->refreshExpiresIn = $responseArray['refresh_expires_in'];
        $this->refreshToken = $responseArray['refresh_token'] ?? null;
        $this->tokenType = $responseArray['token_type'];
    }

    public function accessToken(): string
    {
        return $this->accessToken;
    }

    public function expiresIn(): int
    {
        return $this->expiresIn;
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

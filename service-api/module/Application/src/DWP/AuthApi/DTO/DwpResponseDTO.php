<?php

declare(strict_types=1);

namespace Application\DWP\AuthApi\DTO;

use Application\Services\Auth\DTO\ResponseDTO;

class DwpResponseDTO extends ResponseDTO
{
    public readonly string $accessToken;
    public readonly string|int $expiresIn;
    public readonly string $tokenType;

    public function __construct(array $responseArray) {
        $this->accessToken = $responseArray['access_token'];
        $this->expiresIn = $responseArray['expires_in'];
        $this->tokenType = $responseArray['token_type'];
    }

    public function accessToken(): string
    {
        return $this->accessToken;
    }

    public function expiresIn(): string|int
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

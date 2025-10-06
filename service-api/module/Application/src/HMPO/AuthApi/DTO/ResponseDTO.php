<?php

declare(strict_types=1);

namespace Application\HMPO\AuthApi\DTO;

class ResponseDTO
{
    public function __construct(
        public readonly string $accessToken,
        public readonly int $expiresIn,
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
}

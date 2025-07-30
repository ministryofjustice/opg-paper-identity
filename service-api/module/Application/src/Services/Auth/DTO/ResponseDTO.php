<?php

declare(strict_types=1);

namespace Application\Services\Auth\DTO;

/**
 * @psalm-suppress PossiblyUnusedProperty
 */
class ResponseDTO
{
    public function __construct(
        public readonly string $accessToken,
        public readonly string|int $expiresIn,
        public readonly string $tokenType,
    ) {
    }

    public function accessToken(): string
    {
        return $this->accessToken;
    }

    public function expiresIn(): string|int
    {
        return $this->expiresIn;
    }
}

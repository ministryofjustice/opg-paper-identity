<?php

declare(strict_types=1);

namespace Application\Services\Auth\DTO;

abstract class RequestDTO
{
    public function __construct(
        public readonly string $grantType,
        public readonly string $clientId,
        public readonly string $clientSecret,
    ) {
    }

    abstract public function toArray(): array;
}

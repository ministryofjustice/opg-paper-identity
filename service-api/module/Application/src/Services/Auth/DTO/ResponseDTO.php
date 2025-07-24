<?php

declare(strict_types=1);

namespace Application\Services\Auth\DTO;

abstract class ResponseDTO
{
    public abstract function accessToken(): string;
    public abstract function expiresIn(): string|int;
    public abstract function toArray(): array;
}

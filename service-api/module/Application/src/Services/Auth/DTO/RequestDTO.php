<?php

declare(strict_types=1);

namespace Application\Services\Auth\DTO;

abstract class RequestDTO
{
    abstract public function toArray(): array;
}
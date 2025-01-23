<?php

declare(strict_types=1);

namespace Application\DWP\DwpApi\DTO;

class DetailsRequestDTO
{
    public function __construct(
        private readonly string $id,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }
}

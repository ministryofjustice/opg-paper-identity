<?php

declare(strict_types=1);

namespace Application\Services\Experian\FraudApi\DTO;

class ExperianCrosscoreFraudResponseDTO
{
    public function __construct(
        private readonly array $response
    ) {
    }

    public function toArray(): array
    {
        return $this->response;
    }
}

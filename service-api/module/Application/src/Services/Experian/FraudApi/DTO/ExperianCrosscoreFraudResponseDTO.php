<?php

declare(strict_types=1);

namespace Application\Services\Experian\FraudApi\DTO;

use Application\Services\Experian\FraudApi\ExperianCrosscoreFraudApiException;

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

    public function responseHeader(): array
    {
        try {
            return $this->response['responseHeader'];
        } catch (\Exception $exception) {
            throw new ExperianCrosscoreFraudApiException($exception->getMessage());
        }
    }

    public function decision(): string
    {
        try {
            return $this->response['responseHeader']['overallResponse']['decision'];
        } catch (\Exception $exception) {
            throw new ExperianCrosscoreFraudApiException($exception->getMessage());
        }
    }

    public function score(): float
    {
        try {
            return $this->response['responseHeader']['overallResponse']['score'];
        } catch (\Exception $exception) {
            throw new ExperianCrosscoreFraudApiException($exception->getMessage());
        }
    }
}

<?php

declare(strict_types=1);

namespace Application\Services\Experian\FraudApi\DTO;

class ExperianCrosscoreFraudRequestDTO
{
    public function __construct(
        private readonly string $firstName,
        private readonly string $lastName,
        private readonly string $dob,
        private readonly array $address
    ) {
    }

    public function firstName(): string
    {
        return $this->firstName;
    }

    public function lastName(): string
    {
        return $this->lastName;
    }

    public function dob(): string
    {
        return $this->dob;
    }

    public function address(): array
    {
        return $this->address;
    }

    public function toArray(): array
    {
        return [
            'userName' => $this->firstName,
            'password' => $this->lastName,
            'clientId' => $this->dob,
            'clientSecret' => $this->address,
        ];
    }
}

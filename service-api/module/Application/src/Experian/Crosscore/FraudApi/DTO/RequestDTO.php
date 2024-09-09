<?php

declare(strict_types=1);

namespace Application\Experian\Crosscore\FraudApi\DTO;

class RequestDTO
{
    public function __construct(
        private readonly string $firstName,
        private readonly string $lastName,
        private readonly string $dob,
        private readonly AddressDTO $address
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

    public function address(): AddressDTO
    {
        return $this->address;
    }

    public function toArray(): array
    {
        return [
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'dob' => $this->dob,
            'address' => $this->address->toArray(),
        ];
    }
}

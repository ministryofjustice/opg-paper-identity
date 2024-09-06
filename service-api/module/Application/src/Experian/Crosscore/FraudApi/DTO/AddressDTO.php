<?php

declare(strict_types=1);

namespace Application\Experian\Crosscore\FraudApi\DTO;

class AddressDTO
{
    public function __construct(
        private readonly string $line1,
        private readonly ?string $line2,
        private readonly ?string $line3,
        private readonly ?string $town,
        private readonly string $postcode,
        private readonly ?string $country
    ) {
    }

    public function line1(): string
    {
        return $this->line1;
    }

    public function line2(): ?string
    {
        return $this->line2;
    }

    public function line3(): ?string
    {
        return $this->line3;
    }

    public function town(): ?string
    {
        return $this->town;
    }

    public function postcode(): string
    {
        return $this->postcode;
    }

    public function country(): ?string
    {
        return $this->country;
    }

    public function toArray(): array
    {
        return [
            'line1' => $this->line1,
            'line2' => $this->line2,
            'line3' => $this->line3,
            'town' => $this->town,
            'postcode' => $this->postcode,
            'country' => $this->country
        ];
    }
}

<?php

declare(strict_types=1);

namespace Application\DWP\DwpApi\DTO;

use Application\DWP\DwpApi\DwpApiException;

class DetailsResponseDTO
{
    private string $firstName;
    private string $lastName;
    private string $nino;
    private string $dob;
    private string $verified;

    public function __construct(array $response)
    {
        try {
            $this->firstName = $response['data']['attributes']['name']['firstName'];
            $this->lastName = $response['data']['attributes']['name']['lastName'];
            $this->nino = $response['data']['attributes']['nino'];
            $this->dob = $response['data']['attributes']['dateOfBirth']['date'];
            $this->verified = $response['data']['attributes']['identityVerificationStatus'];
        } catch (\Exception $exception) {
            throw new DwpApiException($exception->getMessage());
        }
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

    public function nino(): string
    {
        return $this->nino;
    }

    public function verified(): string
    {
        return $this->verified;
    }

    public function toArray(): array
    {
        return [
            'firstName' => $this->firstName(),
            'lastName' => $this->lastName(),
            'dob' => $this->dob(),
            'nino' => $this->nino(),
            'verified' => $this->verified(),
        ];
    }
}

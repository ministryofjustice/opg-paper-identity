<?php

declare(strict_types=1);

namespace Application\DWP\DwpApi\DTO;

use Application\DWP\DwpApi\DwpApiException;
use Application\Model\Entity\CaseData;

class CitizenRequestDTO
{
    private $firstName;
    private $lastName;
    private $dob;
    private $postcode;
    private $addressLine1;

    public function __construct(
        protected array $caseData
    ) {
        try {
            $this->postcode = $this->caseData['address']['postcode'];
            $this->addressLine1 = $this->caseData['address']['line1'];
            $this->dob = $this->caseData['dob'];
            $this->firstName = $this->caseData['firstName'];
            $this->lastName = $this->caseData['lastName'];
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

    public function postcode(): string
    {
        return $this->postcode;
    }

    public function addressLine1(): string
    {
        return $this->addressLine1;
    }

    public function toArray(): array
    {
        return [
            'firstName' => $this->firstName(),
            'lastName' => $this->lastName(),
            'dob' => $this->dob(),
            'postcode' => $this->postcode(),
            'addressLine1' => $this->addressLine1()
        ];
    }
}

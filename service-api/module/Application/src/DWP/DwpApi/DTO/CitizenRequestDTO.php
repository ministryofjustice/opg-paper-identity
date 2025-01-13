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
    private $nino;

    public function __construct(
        protected CaseData $caseData
    ) {
        try {
            if($this->caseData->idMethodIncludingNation->id_method !== 'NATIONAL_INSURANCE_NUMBER') {
                throw new DwpApiException('Identity method is not a national insurance number');
            }

            $this->postcode = $this->caseData->claimedIdentity->address['postcode'];
            $this->addressLine1 = $this->caseData->claimedIdentity->address['line1'];
            $this->dob = $this->caseData->claimedIdentity->dob;
            $this->firstName = $this->caseData->claimedIdentity->firstName;
            $this->lastName = $this->caseData->claimedIdentity->lastName;
            $this->nino = $this->caseData->idMethodIncludingNation->id_value;
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

    public function nino(): string
    {
        return $this->nino;
    }

    public function toArray(): array
    {
        return [
            'firstName' => $this->firstName(),
            'lastName' => $this->lastName(),
            'dob' => $this->dob(),
            'postcode' => $this->postcode(),
            'addressLine1' => $this->addressLine1(),
            'nino' => $this->nino()
        ];
    }
}

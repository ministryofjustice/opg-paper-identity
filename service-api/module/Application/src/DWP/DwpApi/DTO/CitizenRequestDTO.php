<?php

declare(strict_types=1);

namespace Application\DWP\DwpApi\DTO;

use Application\DWP\DwpApi\DwpApiException;
use Application\Model\Entity\CaseData;

class CitizenRequestDTO
{
    private string $firstName;
    private string $lastName;
    private string $dob;
    private string $postcode;
    private string $addressLine1;

    public function __construct(
        protected CaseData $caseData,
        private string $nino
    ) {
        try {
            if ($this->caseData->idMethodIncludingNation?->id_method !== 'NATIONAL_INSURANCE_NUMBER') {
                throw new DwpApiException('Identity method is not a national insurance number');
            }

            if (is_null($this->caseData->claimedIdentity?->address['postcode'])) {
                throw new DwpApiException("Case property is not set: postcode");
            } else {
                /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
                $this->postcode = $this->caseData->claimedIdentity?->address['postcode'];
            }

            if (is_null($this->caseData->claimedIdentity?->address['line1'])) {
                throw new DwpApiException("Case property is not set: address line1");
            } else {
                /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
                $this->addressLine1 = $this->caseData->claimedIdentity?->address['line1'];
            }

            if (is_null($this->caseData->claimedIdentity?->dob)) {
                throw new DwpApiException("Case property is not set: date of birth");
            } else {
                /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
                $this->dob = $this->caseData->claimedIdentity?->dob;
            }

            if (is_null($this->caseData->claimedIdentity?->firstName)) {
                throw new DwpApiException("Case property is not set: first name");
            } else {
                /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
                $this->firstName = $this->caseData->claimedIdentity?->firstName;
            }

            if (is_null($this->caseData->claimedIdentity?->lastName)) {
                throw new DwpApiException("Case property is not set: last name");
            } else {
                /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
                $this->lastName = $this->caseData->claimedIdentity?->lastName;
            }
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

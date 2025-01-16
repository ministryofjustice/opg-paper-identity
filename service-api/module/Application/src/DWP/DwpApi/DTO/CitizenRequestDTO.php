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
    private string $nino;

    public function __construct(
        protected CaseData $caseData
    ) {
        try {
            /**
             * @psalm-suppress PossiblyNullPropertyFetch
             */
            if ($this->caseData->idMethodIncludingNation->id_method !== 'NATIONAL_INSURANCE_NUMBER') {
                throw new DwpApiException('Identity method is not a national insurance number');
            }

            /**
             * @psalm-suppress PossiblyNullArrayAccess
             * @psalm-suppress PossiblyNullPropertyFetch
             * @psalm-suppress PossiblyNullPropertyAssignmentValue
             */
            $this->postcode = $this->caseData->claimedIdentity->address['postcode'];
            /**
             * @psalm-suppress PossiblyNullArrayAccess
             * @psalm-suppress PossiblyNullPropertyAssignmentValue
             */
            $this->addressLine1 = $this->caseData->claimedIdentity->address['line1'];
            /**
             * @psalm-suppress PossiblyNullPropertyAssignmentValue
             * @psalm-suppress PossiblyNullPropertyFetch
             */
            $this->dob = $this->caseData->claimedIdentity->dob;
            /**
             * @psalm-suppress PossiblyNullPropertyAssignmentValue
             * @psalm-suppress PossiblyNullPropertyFetch
             */
            $this->firstName = $this->caseData->claimedIdentity->firstName;
            /**
             * @psalm-suppress PossiblyNullPropertyAssignmentValue
             * @psalm-suppress PossiblyNullPropertyFetch
             */
            $this->lastName = $this->caseData->claimedIdentity->lastName;
            /**
             * @psalm-suppress PossiblyNullPropertyAssignmentValue
             * @psalm-suppress PossiblyNullPropertyFetch
             */
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

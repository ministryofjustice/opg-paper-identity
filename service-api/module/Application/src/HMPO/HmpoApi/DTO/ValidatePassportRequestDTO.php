<?php

declare(strict_types=1);

namespace Application\HMPO\HmpoApi\DTO;

use Application\HMPO\HmpoApi\HmpoApiException;
use Application\Enums\DocumentType;
use Application\Model\Entity\CaseData;

class ValidatePassportRequestDTO
{
    private string $forenames;
    private string $surname;
    private string $dateOfBirth;

    public function __construct(
        protected CaseData $caseData,
        private int $passportNumber
    ) {
        try {
            if ($this->caseData->idMethod?->docType !== DocumentType::Passport->value) {
                throw new HmpoApiException('Identity method is not Passport');
            }

            if (is_null($this->caseData->claimedIdentity?->dob)) {
                throw new HmpoApiException("Case property is not set: date of birth");
            } else {
                /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
                $this->dateOfBirth = $this->caseData->claimedIdentity?->dob;
            }

            if (is_null($this->caseData->claimedIdentity?->firstName)) {
                throw new HmpoApiException("Case property is not set: first name");
            } else {
                /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
                $this->forenames = $this->caseData->claimedIdentity?->firstName;
            }

            if (is_null($this->caseData->claimedIdentity?->lastName)) {
                throw new HmpoApiException("Case property is not set: last name");
            } else {
                /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
                $this->surname = $this->caseData->claimedIdentity?->lastName;
            }
        } catch (\Exception $exception) {
            throw new HmpoApiException($exception->getMessage());
        }
    }

    public function forenames(): string
    {
        return $this->forenames;
    }

    public function surname(): string
    {
        return $this->surname;
    }

    public function dateOfBirth(): string
    {
        return $this->dateOfBirth;
    }

    public function passportNumber(): int
    {
        return $this->passportNumber;
    }

    public function toArray(): array
    {
        return [
            'forenames' => $this->forenames(),
            'surname' => $this->surname(),
            'dateOfBirth' => $this->dateOfBirth(),
            'passportNumber' => $this->passportNumber()
        ];
    }

    public function constructValidatePassportRequestBody(): array
    {
        try {
            return [
                "query" =>
                    "query validatePassport(input: \$input) { validationResult passportCancelled passportLostStolen }",
                "variables" => [
                    "input" => $this->toArray()
                ]
            ];
        } catch (\Exception $exception) {
            throw new HmpoApiException($exception->getMessage());
        }
    }
}

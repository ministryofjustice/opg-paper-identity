<?php

declare(strict_types=1);

namespace Application\Enums;

enum DocumentType: string
{
    case NationalInsuranceNumber = "NATIONAL_INSURANCE_NUMBER";
    case Passport = "PASSPORT";
    case DrivingLicence = "DRIVING_LICENCE";
    case NationalId = "NATIONAL_ID";
    case ResidencePermit = "RESIDENCE_PERMIT";

    public function translate(): string
    {
        return match ($this) {
            self::NationalInsuranceNumber => 'National Insurance Number',
            self::Passport => 'Passport',
            self::DrivingLicence => 'Photocard driving licence',
            self::NationalId => 'National ID',
            self::ResidencePermit => 'UK biometric residence permit',
        };
    }
}

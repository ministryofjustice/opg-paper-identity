<?php

declare(strict_types=1);

namespace Application\PostOffice;

enum DocumentType: string
{
    case DrivingLicence = "DRIVING_LICENCE";
    case NationalId = "NATIONAL_ID";
    case Passport = "PASSPORT";
    case ResidencePermit = "RESIDENCE_PERMIT";

    public function translate(): string
    {
        return match ($this) {
            self::DrivingLicence => 'Driving licence',
            self::NationalId => 'National ID',
            self::Passport => 'Passport',
            self::ResidencePermit => 'Biometric residence permit',
        };
    }
}

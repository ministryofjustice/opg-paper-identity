<?php

declare(strict_types=1);

namespace Application\Enums;

enum DocumentType: string
{
    case NationalInsuranceNumber = "NATIONAL_INSURANCE_NUMBER";
    case Passport = "PASSPORT";
    case DrivingLicense = "DRIVING_LICENCE";
    case NationalId = "NATIONAL_ID";
    case ResidencePermit = "RESIDENCE_PERMIT";
}

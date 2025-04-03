<?php

declare(strict_types=1);

namespace Application\Enums;

enum IdMethod: string
{
    case NationalInsuranceNumber = "NATIONAL_INSURANCE_NUMBER";
    case PassportNumber = "PASSPORT";
    case DrivingLicenceNumber = "DRIVING_LICENCE";
    case OnBehalf = "OnBehalf";
    case PostOffice = "POST_OFFICE";
}

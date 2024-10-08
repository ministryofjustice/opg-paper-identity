<?php

declare(strict_types=1);

namespace Application\Enums;

enum IdMethod: string
{
    case NationalInsuranceNumber = "NATIONAL_INSURANCE_NUMBER";
    case PassportNumber = "PASSPORT";
    case DrivingLicenseNumber = "DRIVING_LICENCE";
    case OnBehalf = "OnBehalf";
    case PostOffice = "POST_OFFICE";
    case CourtOfProtection = "cpr";
    case PostOfficeWithUKPassport = "po_ukp";
    case PostOfficeWithEUPassport = "po_eup";
    case PostOfficeWithInternationalPassport = "po_inp";
    case PostOfficeWithUKDrivingLicence = "po_ukd";
    case PostOfficeWithEUDrivingLicense = "po_eud";
    case PostOfficeWithInternationalDrivingLicence = "po_ind";
    case PostOfficeWithNoneOfTheAbove = "po_n";
}

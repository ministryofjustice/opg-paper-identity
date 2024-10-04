<?php

declare(strict_types=1);

namespace Application\Enums;

enum IdMethod: string
{
    case NationalInsuranceNumber = "nin";
    case PassportNumber = "pn";
    case DrivingLicenseNumber = "dln";
    case OnBehalf = "OnBehalf";
    case PostOffice = "po";
    case CourtOfProtection = "cpr";
    case PostOfficeWithUKPassport = "po_ukp";
    case PostOfficeWithEUPassport = "po_eup";
    case PostOfficeWithInternationalPassport = "po_inp";
    case PostOfficeWithUKDrivingLicence = "po_ukd";
    case PostOfficeWithEUDrivingLicense = "po_eud";
    case PostOfficeWithInternationalDrivingLicence = "po_ind";
    case PostOfficeWithUKBiometricResidencePermit = "po_brp";
    case PostOfficeWithNoneOfTheAbove = "po_n";
}

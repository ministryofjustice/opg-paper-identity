<?php

declare(strict_types=1);

namespace Application\Model;

enum IdMethod: string
{
    case NationalInsuranceNumber = "NATIONAL_INSURANCE_NUMBER";
    case PassportNumber = "PASSPORT";
    case DrivingLicenceNumber = "DRIVING_LICENCE";
    case OnBehalf = "OnBehalf";
    case PostOffice = "po";
    case CourtOfProtection = "cpr";
    case PostOfficeWithUKPassport = "po_ukp";
    case PostOfficeWithEUPassport = "po_eup";
    case PostOfficeWithInternationalPassport = "po_inp";
    case PostOfficeWithUKDrivingLicence = "po_ukd";
    case PostOfficeWithEUDrivingLicence = "po_eud";
    case PostOfficeWithInternationalDrivingLicence = "po_ind";
    case PostOfficeWithNoneOfTheAbove = "po_n";
    case IntlPassport = 'xpn';
    case PhotocardDrivingLicence = 'xdln';
    case NationalIdentityCard = 'xid';
    case EUId = 'euid';
}

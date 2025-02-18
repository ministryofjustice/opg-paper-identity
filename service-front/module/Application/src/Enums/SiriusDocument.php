<?php

declare(strict_types=1);

namespace Application\Enums;

enum SiriusDocument: string
{
    case VouchInvitation = "DLP-VOUCH-INVITE";
    case PostOfficeDocCheckDonor = "DLP-ID-PO-D";
    case PostOfficeDocCheckVoucher = "DLP-ID-PO-V";
}

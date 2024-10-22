<?php

declare(strict_types=1);

namespace Application\Enums;

enum SiriusDocument: string
{
    case VouchInvitation = "DLP-VOUCH-INVITE";
    case PostOfficeDocCheck = "DLP-ID-PO-D";
}
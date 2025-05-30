<?php

declare(strict_types=1);

namespace Application\Enums;

enum PersonType: string
{
    case Donor = 'donor';
    case CertificateProvider = 'certificateProvider';
    case Voucher = 'voucher';

    public function translate(): string
    {
        return match ($this) {
            self::Donor => 'donor',
            self::CertificateProvider => 'certificate provider',
            self::Voucher => 'person vouching',
        };
    }
}

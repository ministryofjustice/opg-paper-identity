<?php

declare(strict_types=1);

namespace Application\Enums;

enum LpaStatusType: string
{
    case Draft = 'draft';
    case InProgress = 'in-progress';
    case StatutoryWaitingPeriod = 'statutory-waiting-period';
    case Registered = 'registered';
    case Suspended = 'suspended';
    case DoNotRegister = 'do-not-register';
    case Expired = 'expired';
    case CannotRegister = 'cannot-register';
    case Cancelled = 'cancelled';
    case DeRegistered = 'de-registered';

    /**
    * @psalm-suppress PossiblyUnusedMethod
    */
    public function translate(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::InProgress => 'In progress',
            self::StatutoryWaitingPeriod => 'Statutory waiting period',
            self::Registered => 'Registered',
            self::Suspended => 'Suspended',
            self::DoNotRegister => 'Do not register',
            self::Expired => 'Expired',
            self::CannotRegister => 'Cannot register',
            self::Cancelled => 'Cancelled',
            self::DeRegistered => 'De-registered',
        };
    }
}

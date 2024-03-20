<?php

declare(strict_types=1);

namespace Application\Validators;

use Laminas\Validator\AbstractValidator;

class PassportDateValidator extends AbstractValidator
{
    public const PASSPORT_DATE = 'passport_date';

    protected array $messageTemplates = [
        self::PASSPORT_DATE => 'The passport needs to be no more than 5 years out of date. Check the expiry date 
        and change to Yes, or try a different method',
    ];

    public function isValid($value): bool
    {
        $this->setValue($value);

        return $this->fiveYearValidity($value);
    }

    private function fiveYearValidity(string $date): bool
    {
        $now = time();

        $effectiveExpiry = date('Y-m-d', strtotime('+5 years', strtotime($date)));

        return $now < strtotime($effectiveExpiry);
    }
}

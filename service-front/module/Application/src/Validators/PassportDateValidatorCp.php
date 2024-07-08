<?php

declare(strict_types=1);

namespace Application\Validators;

use Laminas\Validator\AbstractValidator;

class PassportDateValidatorCp extends AbstractValidator
{
    public const PASSPORT_DATE = 'passport_date';

    public const EXPIRY_ALLOWANCE = '+3 year';

    protected array $messageTemplates = [
        self::PASSPORT_DATE => 'The passport needs to be no more than 3 years out of date. Check the expiry date 
        and change to Yes, or try a different method',
    ];

    public function isValid($value): bool
    {
        $this->setValue($value);

        return $this->fiveYearValidity($value);
    }

    private function fiveYearValidity(string $date): bool
    {
        try {
            $now = time();
            $expiryDate = strtotime($date);
            if ($expiryDate === false) {
                return false;
            }
            $effectiveExpiry = date('Y-m-d', strtotime(self::EXPIRY_ALLOWANCE, $expiryDate));

            return $now < strtotime($effectiveExpiry);
        } catch (\Exception $exception) {
            return false;
        }
    }
}

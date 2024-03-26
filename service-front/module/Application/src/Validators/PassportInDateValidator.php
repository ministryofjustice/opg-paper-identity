<?php

declare(strict_types=1);

namespace Application\Validators;

use Laminas\Validator\AbstractValidator;

class PassportInDateValidator extends AbstractValidator
{
    public const PASSPORT_DATE = 'passport_date';
    public const PASSPORT_CONFIRM = 'passport_confirm';
    protected array $messageTemplates = [
        self::PASSPORT_DATE => 'The passport needs to be no more than 5 years out of date. Check the expiry date 
        and change to Yes, or try a different method',
        self::PASSPORT_CONFIRM => 'Please choose yes or no',
    ];

    public function isValid($value): bool
    {
        $this->setValue($value);

        if (empty($this->value) || $this->value === '') {
            $this->error(self::PASSPORT_CONFIRM);
            return false;
        }

        if ($this->value === 'no') {
            $this->error(self::PASSPORT_DATE);
            return false;
        }

        if ($this->value === 'yes') {
            $this->error(self::PASSPORT_DATE);
            return true;
        }
        return false;
    }
}

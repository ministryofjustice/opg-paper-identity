<?php

declare(strict_types=1);

namespace Application\Validators;

use Laminas\Validator\AbstractValidator;

class DLNDateValidator extends AbstractValidator
{
    public const DLN_DATE = 'DLN_date';
    public const DLN_CONFIRM = 'DLN_confirm';
    protected array $messageTemplates = [
        self::DLN_DATE => 'The driving licence needs to be in date. ' .
            'Check the expiry date and change to Yes, or try a different method',
        self::DLN_CONFIRM => 'Please choose yes or no',
    ];

    public function isValid($value): bool
    {
        $this->setValue($value);

        if (empty($this->value) || $this->value === '') {
            $this->error(self::DLN_CONFIRM);
            return false;
        }

        if ($this->value === 'no') {
            $this->error(self::DLN_DATE);
            return false;
        }

        if ($this->value === 'yes') {
            return true;
        }
        return false;
    }
}

<?php

declare(strict_types=1);

namespace Application\Validators;

use Laminas\Validator\AbstractValidator;

class LpaUidValidator extends AbstractValidator
{
    public const LPA = 'lpa';
    public const EMPTY = 'empty';

    protected array $messageTemplates = [
        self::EMPTY => 'Enter an LPA number to continue.',
        self::LPA => 'The LPA needs to be valid in the format M-XXXX-XXXX-XXXX',
    ];

    public function isValid($value): bool
    {
        if (empty($value)) {
            $this->error(self::EMPTY);
            return false;
        }

        $this->setValue($value);

        if (! $this->lpaValidity()) {
            $this->error(self::LPA);
            return false;
        }

        return true;
    }

    private function lpaValidity(): bool
    {
        return 1 === preg_match('/^M(-([0-9A-Z]){4}){3}$/', $this->value);
    }
}

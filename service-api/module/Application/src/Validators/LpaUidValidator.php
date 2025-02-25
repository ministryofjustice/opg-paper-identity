<?php

declare(strict_types=1);

namespace Application\Validators;

use Laminas\Validator\AbstractValidator;

class LpaUidValidator extends AbstractValidator
{
    public const LPA = 'lpa';

    protected array $messageTemplates = [
        self::LPA => 'The LPA needs to be valid in the format M-XXXX-XXXX-XXXX',
    ];

    public function isValid($value): bool
    {
        $this->setValue($value);

        return $this->lpaValidity($value);
    }

    private function lpaValidity(?string $lpa): bool
    {
        if ($lpa === null || $lpa === '') {
            return true;
        }

        return 1 === preg_match('/^M(-([0-9A-Z]){4}){3}$/', $lpa);
    }
}

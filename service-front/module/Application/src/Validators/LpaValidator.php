<?php

declare(strict_types=1);

namespace Application\Validators;

use Laminas\Validator\AbstractValidator;

class LpaValidator extends AbstractValidator
{
    public const LPA = 'lpa';

    protected array $messageTemplates = [
        self::LPA => 'The LPA needs to be valid in the format M-XXXX-XXXX-XXXX',
    ];

    public function isValid($value): bool
    {
        $this->setValue(strtoupper($value));

        if (! $this->lpaValidity()) {
            $this->error(self::LPA);
            return false;
        }

        return true;
    }

    private function lpaValidity(): bool
    {
        /** @var string $this->>value */
        if (1 === preg_match('/M(-([0-9A-Z]){4}){3}/', $this->value)) {
            return true;
        } else {
            return false;
        }
    }
}

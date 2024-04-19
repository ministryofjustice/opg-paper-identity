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

        if (!$this->lpaValidity($this->value)) {
            $this->error(self::LPA);
            return false;
        }

        return true;
    }

    private function lpaValidity(?string $lpa): bool
    {
        /** @var string $lpa */
        if (1 === preg_match('/M(-([0-9A-Z]){4}){3}/', $lpa)) {
            return true;
        } else {
            return false;
        }
    }
}

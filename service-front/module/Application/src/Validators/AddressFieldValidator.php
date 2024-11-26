<?php

declare(strict_types=1);

namespace Application\Validators;

use Laminas\Validator\AbstractValidator;

class AddressFieldValidator extends AbstractValidator
{
    public const EMPTY = 'empty';

    protected array $messageTemplates = [
        self::EMPTY => 'Value cannot be empty',
    ];

    public function isValid($value): bool
    {
        if (empty($value) || $value === '') {
            $this->error(self::EMPTY);
            return false;
        }

        return true;
    }
}

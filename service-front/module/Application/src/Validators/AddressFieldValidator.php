<?php

declare(strict_types=1);

namespace Application\Validators;

use Laminas\Validator\AbstractValidator;

class AddressFieldValidator extends AbstractValidator
{
    public const ADDRESS = 'address';
    public const EMPTY = 'empty';

    protected array $messageTemplates = [
        self::EMPTY => 'Value cannot be empty',
        self::ADDRESS => "The address line contains invalid characters. Only alphanumeric plus '.,- ",
    ];

    public function isValid($value): bool
    {
        if (empty($value)) {
            $this->error(self::EMPTY);
            return false;
        }

        $this->setValue($value);

        if (! $this->lpaValidity()) {
            $this->error(self::ADDRESS);
            return false;
        }

        return true;
    }

    private function lpaValidity(): bool
    {
        /** @var string $this->>value */
        if (1 === preg_match('/[A-Za-z0-9\'.-s,]/', $this->value)) {
            return true;
        } else {
            return false;
        }
    }
}

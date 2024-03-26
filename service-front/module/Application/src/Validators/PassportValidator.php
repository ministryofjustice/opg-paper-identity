<?php

declare(strict_types=1);

namespace Application\Validators;

use Laminas\Validator\AbstractValidator;

class PassportValidator extends AbstractValidator
{
    public const PASSPORT_FORMAT = 'passport_format';
    public const PASSPORT_COUNT = 'passport_count';
    public const PASSPORT_COUNT_NUMBER = 9;
    public const PASSPORT_PATTERN = "^\s*[0-9]+\s*$^";
    protected array $messageTemplates = [
        self::PASSPORT_FORMAT => "The passport number is not the correct format. Try again.",
        self::PASSPORT_COUNT => "Enter the full " . self::PASSPORT_COUNT_NUMBER . " digits of the passport number.",
    ];

    public function isValid($value): bool
    {
        $this->setValue($this->formatValue($value));

        if (! $this->checkLength() === true) {
            $this->error(self::PASSPORT_COUNT);
            return false;
        }

        if (! $this->checkPattern() == 1) {
            $this->error(self::PASSPORT_FORMAT);
            return false;
        }
        return true;
    }

    private function formatValue(string $value): string
    {
        return strtoupper(preg_replace('/(\s+)|(-)/', '', $value));
    }

    private function checkPattern(): int
    {
        return preg_match(self::PASSPORT_PATTERN, $this->value);
    }

    private function checkLength(): bool
    {
        return strlen($this->value) == self::PASSPORT_COUNT_NUMBER;
    }
}

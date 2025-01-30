<?php

declare(strict_types=1);

namespace Application\Validators;

use Laminas\Validator\AbstractValidator;

class DLNValidator extends AbstractValidator
{
    public const DLN_EMPTY = 'DLN_empty';
    public const DLN_FORMAT = 'DLN_format';
    public const DLN_COUNT = 'DLN_count';
    public const DLN_COUNT_NUMBER = 16;
    public const DLN_PATTERN = "^[A-Z9<]{5}[0-9<]{6}[A-Z9]{2}[A-Z0-9]{3}$^";
    protected array $messageTemplates = [
        self::DLN_EMPTY => "Enter the Driving licence number.",
        self::DLN_FORMAT => "The driving licence number is not the correct format. Try again.",
        self::DLN_COUNT => "Enter the full " . self::DLN_COUNT_NUMBER . " driving licence number.",
    ];

    public function isValid($value): bool
    {
        $this->setValue($this->formatValue($value));

        if (empty($this->value)) {
            $this->error(self::DLN_EMPTY);
            return false;
        }

        if (! $this->checkLength() === true) {
            $this->error(self::DLN_COUNT);
            return false;
        }

        if (! $this->checkPattern() == 1) {
            $this->error(self::DLN_FORMAT);
            return false;
        }
        return true;
    }

    private function formatValue(string $value): string
    {
        return strtoupper(preg_replace('/(\s+)|(-)/', '', $value) ?? '');
    }

    private function checkPattern(): int
    {
        return preg_match(self::DLN_PATTERN, $this->value) ?: 0;
    }

    private function checkLength(): bool
    {
        return strlen($this->value) == self::DLN_COUNT_NUMBER;
    }
}

<?php

declare(strict_types=1);

namespace Application\Validators;

use Laminas\Validator\AbstractValidator;

class NinoValidator extends AbstractValidator
{
    public const NINO_EMPTY = 'nino_empty';
    public const NINO_FORMAT = 'nino_format';
    public const NINO_COUNT = 'nino_count';
    public const NINO_COUNT_NUMBER = 9;
    protected array $messageTemplates = [
        self::NINO_EMPTY => "Enter the National insurance number.",
        self::NINO_FORMAT => "The National insurance number is not the correct format. Try again.",
        self::NINO_COUNT => "Enter the full 9 characters of the NI number. " .
        "2 letters, 6 numbers and a final letter, which is always A, B, C, or D",
    ];

    public function isValid($value): bool
    {
        $this->setValue($this->formatValue($value));

        if (empty($this->value)) {
            $this->error(self::NINO_EMPTY);
            return false;
        }

        if (! $this->checkLength() === true) {
            $this->error(self::NINO_COUNT);
            return false;
        }

        if (! $this->checkPattern() == 1) {
            $this->error(self::NINO_FORMAT);
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
        return preg_match("/^[A-CEGHJ-PR-TW-Z][A-CEGHJ-NPR-TW-Z][0-9]{6}[A-D]$/i", $this->value);
    }

    private function checkLength(): bool
    {
        return strlen($this->value) == self::NINO_COUNT_NUMBER;
    }
}

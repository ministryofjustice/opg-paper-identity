<?php

declare(strict_types=1);

namespace Application\Validators;

use Laminas\Validator\AbstractValidator;

class NinoValidator extends AbstractValidator
{
    public const NINO_FORMAT = 'nino_format';
    public const NINO_COUNT = 'nino_count';
    protected array $messageTemplates = [
        self::NINO_FORMAT => "The National insurance number is not the correct format. Try again.",
        self::NINO_COUNT => "Enter the full 9 characters of the NI number.  2 letters, 6 numbers 
        and a final letter, which is always A, B, C, or D",
    ];

    public function isValid($value): bool
    {
        $this->setValue($this->formatValue($value));

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
        return preg_match("/^[A-CEGHJ-PR-TW-Z]{1}[A-CEGHJ-NPR-TW-Z]{1}[0-9]{6}[A-D]{1}$/i", $this->value);
    }

    private function checkLength(): bool
    {
        return strlen($this->value) == 9;
    }
}

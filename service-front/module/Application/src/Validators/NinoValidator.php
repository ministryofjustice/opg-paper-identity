<?php

declare(strict_types=1);

namespace Application\Validators;

use Laminas\Validator\AbstractValidator;

class NinoValidator extends AbstractValidator
{
    const NINO = 'nino';

    protected $messageTemplates = [
        self::NINO => "'%value%' is not a valid National Insurance Number",
    ];

    public function isValid($value)
    {
        $this->setValue($this->formatValue($value));

        if (!$this->checkPattern($this->value) == 1) {
            $this->error(self::NINO);
            return false;
        }
        return true;
    }

    private function formatValue(string $value): string
    {
        return strtoupper(preg_replace('/(\s+)|(-)/', '', $value));
    }

    private function checkPattern(string $value): int
    {
        return preg_match("/^[A-CEGHJ-PR-TW-Z]{1}[A-CEGHJ-NPR-TW-Z]{1}[0-9]{6}[A-D]{1}$/i", $value);
    }
}

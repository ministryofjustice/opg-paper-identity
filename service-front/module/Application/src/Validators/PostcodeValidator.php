<?php

declare(strict_types=1);

namespace Application\Validators;

use GuzzleHttp\Promise\Is;
use Laminas\Validator\AbstractValidator;

class PostcodeValidator extends AbstractValidator
{
    public const POSTCODE_FORMAT = 'postcode_format';
    public const EMPTY = 'empty';

    protected array $messageTemplates = [
        self::EMPTY => "Enter a postcode",
        self::POSTCODE_FORMAT => "Enter a valid postcode",
    ];

    public function isValid($value, ?array $context = null): bool
    {
        $this->setValue($this->formatValue($value));

        if (! isset($context['country']) || empty($context['country']) || $context['country'] === "United Kingdom") {
            if (empty($value)) {
                $this->error(self::EMPTY);
                return false;
            } elseif (! $this->checkPattern() == 1) {
                $this->error(self::POSTCODE_FORMAT);
                return false;
            }
        }
        return true;
    }

    private function formatValue(string $value): string
    {
        return strtoupper(preg_replace('/(\s+)|(-)/', '', $value));
    }

    private function checkPattern(): int
    {

        return preg_match(
        // phpcs:ignore
            '/^([Gg][Ii][Rr] 0[Aa]{2})|((([A-Za-z][0-9]{1,2})|(([A-Za-z][A-Ha-hJ-Yj-y][0-9]{1,2})|(([A-Za-z][0-9][A-Za-z])|([A-Za-z][A-Ha-hJ-Yj-y][0-9][A-Za-z]?))))\s?[0-9][A-Za-z]{2})$/',
            $this->value
        );
    }
}

<?php

declare(strict_types=1);

namespace Application\Validators;

use DateTime;
use Laminas\Validator\AbstractValidator;

class BirthDateValidator extends AbstractValidator
{
    public const DATE_FORMAT = 'date_format';
    public const DATE_EMPTY = 'date_empty';
    public const DATE_18 = 'date_under_18';
    public const DATE_FUTURE = 'date_future';
    public const EIGHTEEN_YEARS = '-18 year';

    protected array $messageTemplates = [
        self::DATE_FORMAT => 'The date needs to be a valid date.',
        self::DATE_EMPTY => 'The date cannot be empty.',
        self::DATE_18 => 'Birth date cannot be under 18 years ago.',
        self::DATE_FUTURE => 'Date of birth must be in the past.'
    ];

    private function isRealDate(string $value): bool
    {
        $formats = ['Y-m-d', 'Y-n-j'];

        foreach ($formats as $format) {
            $dateTime = DateTime::createFromFormat($format, $value);

            // Check if the parsing was successful and the parsed date matches the input
            if ($dateTime && $dateTime->format($format) === $value) {
                return true;
            }
        }

        return false;
    }

    public function isValid($value): bool
    {
        $this->setValue($value);

        // Check for empty date values or invalid placeholder like '--'
        if (empty($this->value) || $this->value === '' || $this->value === '--') {
            $this->error(self::DATE_EMPTY);
            return false;
        }

        // Check if the date is in valid format
        try {
            $dateTime = new \DateTime($this->value);
        } catch (\Exception $exception) {
            $this->error(self::DATE_FORMAT);
            return false;
        }

        // Check if the date is in the future
        $currentDate = new \DateTime();
        if ($dateTime > $currentDate) {
            $this->error(self::DATE_FUTURE);
            return false;
        }

        // Validate that the date represents an age of at least 18
        $birthDate = strtotime($value);
        $minBirthDate = strtotime(self::EIGHTEEN_YEARS, time());


        // If the birth date is less than 18 years ago, return an error
        if ($birthDate > $minBirthDate) {
            $this->error(self::DATE_18);
            return false;
        }

        // Check if the date is a real valid date (no impossible dates - eg: 29th Feb, except for leap year)
        if (! $this->isRealDate($value)) {
            $this->error(self::DATE_FORMAT);
            return false;
        }

        return true;
    }
}

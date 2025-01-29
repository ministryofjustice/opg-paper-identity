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

    public const EIGHTEEN_YEARS = '-18 year';

    protected array $messageTemplates = [
        self::DATE_FORMAT => 'Date of birth must be a valid date',
        self::DATE_EMPTY => 'Enter their date of birth',
        self::DATE_18 => 'The person must be 18 years or older.',
    ];

    public function isValid($value): bool
    {
        $this->setValue($value);

        if (empty($this->value) || $this->value === '' || $this->value === '--') {
            $this->error(self::DATE_EMPTY);
            return false;
        }

        try {
            new \DateTime($this->value);
        } catch (\Exception $exception) {
            $this->error(self::DATE_FORMAT);
            return false;
        }

        $birthDate = strtotime($value);
        $minBirthDate = strtotime(self::EIGHTEEN_YEARS, time());

        if ($birthDate > $minBirthDate) {
            $this->error(self::DATE_18);
            return false;
        }
        return true;
    }
}

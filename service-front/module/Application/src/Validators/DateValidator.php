<?php

declare(strict_types=1);

namespace Application\Validators;

use Laminas\Validator\AbstractValidator;

class DateValidator extends AbstractValidator
{
    public const DATE_FORMAT = 'date_format';

    public const DATE_EMPTY = 'passport_date_empty';
    public const DATE_PAST = 'passport_date_past';

    protected array $messageTemplates = [
        self::DATE_FORMAT => 'The date needs to be a valid date',
        self::DATE_EMPTY => 'The date cannot be empty',
        self::DATE_PAST => 'The date cannot be in the future',
    ];

    public function isValid($value): bool
    {
        $this->setValue($value);

        if (empty($this->value) || $this->value === '') {
            $this->error(self::DATE_EMPTY);
            return false;
        }

        try {
            $date = new \DateTime($this->value);
        } catch (\Exception $exception) {
            $this->error(self::DATE_FORMAT);
            return false;
        }

        if ($date > new \DateTime()) {
            $this->error(self::DATE_PAST);
            return false;
        }
        return true;
    }
}

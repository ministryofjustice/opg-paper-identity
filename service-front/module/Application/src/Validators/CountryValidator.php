<?php

declare(strict_types=1);

namespace Application\Validators;

use Application\PostOffice\Country;
use Laminas\Validator\AbstractValidator;

class CountryValidator extends AbstractValidator
{
    public const INVALID_COUNTRY = 'invalid_country';
    public const EMPTY_COUNTRY = 'empty_country';
    protected array $messageTemplates = [
        self::INVALID_COUNTRY => "This country code is not recognised",
        self::EMPTY_COUNTRY => "Please select a country",
    ];

    public function isValid($value): bool
    {
        if (empty($value)) {
            $this->error(self::EMPTY_COUNTRY);
            return false;
        }

        if (! is_string($value) || Country::tryFrom($value) === null) {
            $this->error(self::INVALID_COUNTRY);
            return false;
        }

        return true;
    }
}

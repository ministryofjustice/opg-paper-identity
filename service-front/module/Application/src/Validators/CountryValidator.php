<?php

declare(strict_types=1);

namespace Application\Validators;

use Laminas\Di\Config;
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
        $countryCodes = $this->getCountryCodes();

        if (empty($value)) {
            $this->error(self::EMPTY_COUNTRY);
            return false;
        }

        if (! array_key_exists($value, $countryCodes)) {
            $this->error(self::INVALID_COUNTRY);
            return false;
        }
        return true;
    }

    private function getCountryCodes(): array
    {
        $config = $this->getConfig();

        return $config['opg_settings']['acceptable_nations_for_id_documents'];
    }

    public function getConfig(): array
    {
        return include __DIR__ . './../../config/module.config.php';
    }
}

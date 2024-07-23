<?php

declare(strict_types=1);

namespace Application\Validators;

use Laminas\Di\Config;
use Laminas\Validator\AbstractValidator;

class CountryDocumentValidator extends AbstractValidator
{
    public const INVALID_DOCUMENT = 'invalid_document';

    protected array $messageTemplates = [
        self::INVALID_DOCUMENT => "This document code is not recognised",
    ];

    public function isValid($value): bool
    {
        $documentCodes = $this->getDocumentCodes();

        if (! array_key_exists($value, $documentCodes)) {
            $this->error(self::INVALID_DOCUMENT);
            return false;
        }
        return true;
    }

    private function getDocumentCodes(): array
    {
        $config = $this->getConfig();

        return $config['opg_settings']['non_uk_identity_methods'];
    }

    public function getConfig(): array
    {
        return include __DIR__ . './../../config/module.config.php';
    }
}

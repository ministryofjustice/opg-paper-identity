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

        if (! in_array($value, $documentCodes)) {
            $this->error(self::INVALID_DOCUMENT);
            return false;
        }
        return true;
    }

    private function getDocumentCodes(): array
    {
        $config = $this->getConfig();
        $docTypeList = [];

        foreach ($config['opg_settings']['supported_countries_documents'] as $docBody) {
            foreach ($docBody['supported_documents'] as $docType) {
                if (! in_array($docType['type'], $docTypeList)) {
                    $docTypeList[] = $docType['type'];
                }
            }
        }
        return $docTypeList;
    }

    public function getConfig(): array
    {
        return include __DIR__ . './../../config/module.config.php';
    }
}

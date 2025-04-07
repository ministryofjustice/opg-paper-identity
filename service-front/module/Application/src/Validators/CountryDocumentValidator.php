<?php

declare(strict_types=1);

namespace Application\Validators;

use Application\Enums\DocumentType;
use Laminas\Validator\AbstractValidator;

class CountryDocumentValidator extends AbstractValidator
{
    public const INVALID_DOCUMENT = 'invalid_document';

    protected array $messageTemplates = [
        self::INVALID_DOCUMENT => "This document code is not recognised",
    ];

    public function isValid($value): bool
    {
        if (! is_string($value) || DocumentType::tryFrom($value) === null) {
            $this->error(self::INVALID_DOCUMENT);

            return false;
        }

        return true;
    }
}

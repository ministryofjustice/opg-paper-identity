<?php

declare(strict_types=1);

namespace Application\PostOffice;

use Application\Enums\IdMethod;

/**
 * @psalm-type CountryDocumentConfig
 */
class DocumentTypeRepository
{
    /**
     * @param array{
     *   code: string,
     *   supported_documents: array{
     *     type: string
     *   }[]
     * }[] $supportedDocuments
     */
    public function __construct(private readonly array $supportedDocuments)
    {
    }

    /**
     * @return DocumentType[]
     */
    public function getByCountry(Country $country): array
    {
        $allowed = [
            DocumentType::Passport,
        ];

        if ($country->isEUOrEEA()) {
            $allowed[] = DocumentType::DrivingLicence;
            $allowed[] = DocumentType::NationalId;
        }

        $available = [];

        foreach ($this->supportedDocuments as $countryDocs) {
            if ($countryDocs['code'] === $country->value) {
                foreach ($countryDocs['supported_documents'] as $supportedDoc) {
                    $docType = DocumentType::tryFrom($supportedDoc['type']);

                    if (! is_null($docType) && in_array($docType, $allowed)) {
                        if ($docType->value === IdMethod::PassportNumber->value) {
                            $available = array_merge([0 => $docType], $available);
                        } else {
                            $available[] = $docType;
                        }
                    }
                }
                break;
            }
        }

        return $available;
    }
}

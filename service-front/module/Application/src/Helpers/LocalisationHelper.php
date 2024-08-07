<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Exceptions\LocalisationException;

class LocalisationHelper
{
    public function __construct(private readonly array $config)
    {
    }

    private array $wordMap = [
        'PASSPORT' => "Passport",
        'DRIVING_LICENCE' => 'Driving licence',
        'NATIONAL_ID' => 'National ID',
        'RESIDENCE_PERMIT' => 'Residence permit',
        'TRAVEL_DOCUMENT' => 'Travel document',
    ];

    /**
     * @throws LocalisationException
     */
    public function getInternationalSupportedDocuments(array $detailsData): array
    {
        $config = $this->getConfig();

        $idDocuments = $config['opg_settings']['supported_countries_documents'];
        $documents = [];

        if (! isset($detailsData['idMethodIncludingNation']['country'])) {
            throw new LocalisationException("Country for document list has not been set.");
        }

        foreach ($idDocuments as $countryDocumentBody) {
            if ($countryDocumentBody['code'] == $detailsData['idMethodIncludingNation']['country']) {
                $documents = $countryDocumentBody;
            }
        }

        if ($documents === []) {
            throw new LocalisationException(
                "Country for document list has not been found in config."
            );
        }
        return $this->processDocumentBody($documents);
    }

    public function processDocumentBody(array $documents): array
    {
        foreach ($documents['supported_documents'] as $key => $value) {
            $string = $this->addDisplayText($value['type']);

            $documents['supported_documents'][$key] = array_merge(
                $documents['supported_documents'][$key],
                ['display_text' => $string]
            );
        }
        return $documents;
    }

    public function addDisplayText(string $word): string
    {
        return $this->wordMap[$word];
    }

    private function getConfig(): array
    {
        return $this->config;
    }
}

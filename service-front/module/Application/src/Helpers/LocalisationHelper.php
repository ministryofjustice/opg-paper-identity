<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Exceptions\LocalisationException;

class LocalisationHelper
{
    public function __construct(private readonly array $config)
    {
    }

    /**
     * @throws LocalisationException
     */
    public function getInternationalSupportedDocuments(string $countryCode): array
    {
        $config = $this->getConfig();

        $idDocuments = $config['opg_settings']['supported_countries_documents'];
        $documents = [];

        foreach ($idDocuments as $countryDocumentBody) {
            if ($countryDocumentBody['code'] == $countryCode) {
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

    /**
     * @throws LocalisationException
     */
    public function addDisplayText(string $word): string
    {
        if (array_key_exists($word, $this->config['opg_settings']['yoti_identity_methods'])) {
            return $this->config['opg_settings']['yoti_identity_methods'][$word];
        } else {
            throw new LocalisationException("This identity document type is not supported.");
        }
    }

    public function getDocumentTypeString(array $detailsData): string
    {
        $key = $detailsData['idMethod'];

        if (array_key_exists($key, $this->config['opg_settings']['post_office_identity_methods'])) {
            return $this->config['opg_settings']['post_office_identity_methods'][$key];
        }

        if (array_key_exists('idMethodIncludingNation', $detailsData)) {
            $country =
                $this->config['opg_settings']['acceptable_nations_for_id_documents'][$detailsData['idMethodIncludingNation']['country']];
            $document = $this->config['opg_settings']['identity_documents'];
            return "$document ($country)";
        }
    }

    private function getConfig(): array
    {
        return $this->config;
    }
}

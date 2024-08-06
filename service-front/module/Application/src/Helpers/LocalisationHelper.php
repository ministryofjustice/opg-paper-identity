<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Exceptions\LocalisationException;

class LocalisationHelper
{
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
            $value = (function (array $value): string {
                $string = strtolower($value['type']);
                $descString = '';
                $words = explode("_", $string);
                foreach ($words as $k => $word) {
                    if ($k == 0) {
                        $descString .= ucfirst($word) . " ";
                    } elseif ($word == 'id') {
                        $descString .= strtoupper($word) . " ";
                    } else {
                        $descString .= $word . " ";
                    }
                }
                return substr($descString, 0, strlen($descString) - 1);
            })($value);

            $documents['supported_documents'][$key] = array_merge(
                $documents['supported_documents'][$key],
                ['display_text' => $value]
            );
        }
        return $documents;
    }

    private function getConfig(): array
    {
        return include __DIR__ . './../../config/module.config.php';
    }
}

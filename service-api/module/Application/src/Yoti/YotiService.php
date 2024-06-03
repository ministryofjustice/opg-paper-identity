<?php

declare(strict_types=1);

namespace Application\Yoti;

use GuzzleHttp\Client;

/**
 * @psalm-suppress PossiblyUnusedProperty
 * Suppress unused $client pending implementation
 */
class YotiService implements YotiServiceInterface
{
    public function __construct(
        public readonly Client $client
    ) {
    }

    /**
     * @param string $postCode
     * @return array
     * Get post offices near the location
     */
    public function postOfficeBranch(string $postCode): array
    {
        $results = $this->client->post('/idverify/v1/lookup/uk-post-office', [
            'json' => ['search_string' => $postCode],
        ]);

        return json_decode(strval($results->getBody()), true);
    }

    /**
     * @param array $sessionData
     * @return array
     * Create a IBV session with applicant data and requirements
     */
    public function createSession(array $sessionData): array
    {
        $results = $this->client->post('/idverify/v1/sessions', [
            'json' => $sessionData,
        ]);

        $result = json_decode(strval($results->getBody()), true);

        return ["status" => $results->getStatusCode(), "data" => $result];
    }

    /**
     * @param string $sessionId
     * @return array
     * Look up results of a Post Office IBV session
     */
    public function retrieveResults(string $sessionId): array
    {
        //can either use client directly like below or use
        $results = $this->client->get('/idverify/v1/sessions/' . $sessionId);

        return json_decode(strval($results->getBody()), true);
    }

    /**
     * @param string $sessionId
     * @return array
     * Generate PDF letter for applicant
     */
    public function retrieveLetterPDF(string $sessionId): array
    {
        return [];
    }
}

<?php

declare(strict_types=1);

namespace Application\Yoti;

use Application\Exceptions\YotiException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Laminas\Http\Response;

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
     * @throws GuzzleException
     * @throws YotiException
     */
    public function postOfficeBranch(string $postCode): array
    {
        try {
            $results = $this->client->post('/idverify/v1/lookup/uk-post-office', [
                'json' => ['SearchString' => $postCode],
            ]);
            if ($results->getStatusCode() !== Response::STATUS_CODE_200) {
                throw new YotiException($results->getReasonPhrase());
            }
            return json_decode(strval($results->getBody()), true);
        } catch (GuzzleException $e) {
            return ["error" => $e->getMessage()];
        }
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

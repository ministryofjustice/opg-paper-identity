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
        
        return $results;
    }

    /**
     * @param array $sessionData
     * @return array
     * Create a IBV session with applicant data and requirements
     */
    public function createSession(array $sessionData): array
    {
        return [];
    }

    /**
     * @param string $sessionId
     * @return array
     * Look up results of a Post Office IBV session
     */
    public function retrieveResults(string $sessionId): array
    {
        //can either use client directly like below or use 
        $results = $this->client->get('/idverify/v1/sessions/' . $sessionId, [
            //any 'headers' or 'json' etc here
        ]);
        
      
        return $results;
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
    
    public function makeApiRequest(string $uri, string $verb = 'get', array $data = [], array $headers = []): array
    {
        try {
            $response = $this->client->request($verb, $uri, [
                'headers' => $headers,
                'json' => $data,
                'debug' => true
            ]);

            $this->responseStatus = Response::STATUS_CODE_200;
            $this->responseData = json_decode($response->getBody()->getContents(), true);

            if ($response->getStatusCode() !== Response::STATUS_CODE_200) {
                throw new OpgApiException($response->getReasonPhrase());
            }
            return $this->responseData;
        } catch (\GuzzleHttp\Exception\BadResponseException $exception) {
            throw new OpgApiException($exception->getMessage());
        }
    }
}

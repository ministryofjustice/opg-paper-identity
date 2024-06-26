<?php

declare(strict_types=1);

namespace Application\Yoti;

use Application\Exceptions\YotiException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Laminas\Http\Response;
use Psr\Log\LoggerInterface;

/**
 * @psalm-suppress PossiblyUnusedProperty
 * Suppress unused $client pending implementation
 */
class YotiService implements YotiServiceInterface
{
    public function __construct(
        public readonly Client $client,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param string $postCode
     * @return array{
     *      branches: array{
     *          type: string,
     *          fad_code: string,
     *          name: string,
     *          address: string,
     *          postcode: string,
     *          location: array{
     *              latitude: float,
     *              longitude: float
     *          }
     *      }[]
     *  }
     * Get post offices near the location
     * @throws YotiException
     */
    public function postOfficeBranch(string $postCode): array
    {
        try {
            $results = $this->client->post('/idverify/v1/lookup/uk-post-office', [
                'json' => ['search_string' => $postCode],
            ]);
            if ($results->getStatusCode() !== Response::STATUS_CODE_200) {
                $this->logger->error('Post Office Lookup unsuccessful ', [
                    'data' => [ 'Post Code' => $postCode]
                ]);
                throw new YotiException("FM INT: " . $results->getReasonPhrase());
            }
            return json_decode(strval($results->getBody()), true);
        } catch (GuzzleException $e) {
            $this->logger->error('Unable to connect to Post Office Service [' . $e->getMessage() . '] ', [
                'data' => [ 'Post Code' => $postCode]
            ]);
            throw new YotiException("A connection error occurred. Previous: " . $e->getMessage());
        }
    }

    /**
     * @param array $sessionData
     * @return array
     * Create a IBV session with applicant data and requirements
     */
    public function createSession(array $sessionData): array
    {
        //need to use the RequestSigner for the signature here that is on another branch
        $headers = [
            'X-Yoti-Auth-Digest' => ''
        ];

        $results = $this->client->post('/idverify/v1/sessions', [
            'headers' => $headers,
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

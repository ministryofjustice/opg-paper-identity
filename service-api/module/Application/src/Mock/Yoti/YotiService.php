<?php

declare(strict_types=1);

namespace Application\Mock\Yoti;

use Application\Yoti\YotiServiceInterface;
use GuzzleHttp\Client;

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
        return [];
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
        return [];
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

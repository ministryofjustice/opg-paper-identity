<?php

declare(strict_types=1);

namespace Application\Yoti;

use Application\Exceptions\YotiException;
use Application\Model\Entity\CaseData;

interface YotiServiceInterface
{
    /**
     * @param string $postCode
     * @return array
     * Get post offices near the location
     * @throws YotiException
     */
    public function postOfficeBranch(string $postCode): array;

    /**
     * @param array $sessionData
     * @return array
     * Create a IBV session with applicant data and requirements
     */
    public function createSession(array $sessionData): array;

    /**
     * @param string $sessionId
     * @return array
     * Look up results of a Post Office IBV session
     */
    public function retrieveResults(string $sessionId): array;

    /**
     * @param CaseData $caseData
     * @return array
     * Generate PDF letter for applicant
     */
    public function retrieveLetterPDF(CaseData $caseData): array;
}

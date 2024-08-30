<?php

declare(strict_types=1);

namespace Application\Yoti;

use Application\Model\Entity\CaseData;
use Application\Yoti\Http\Exception\YotiException;

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
    public function createSession(array $sessionData, string $nonce, int $timestamp): array;

    /**
     * @param string $sessionId
     * @return array
     * Look up results of a Post Office IBV session
     */
    public function retrieveResults(string $sessionId, string $nonce, int $timestamp): array;

    /**
     * @param string $sessionId
     * @return array
     * Generate PDF letter for applicant
     */
    public function retrieveLetterPDF(string $sessionId, string $nonce, int $timestamp): array;

    /**
     * @psalm-suppress PossiblyUnusedReturnValue
     * @param CaseData $caseData
     * @return array
     * Prepare PDF letter for applicant
     */
    public function preparePDFLetter(CaseData $caseData, string $nonce, int $timestamp, string $sessionId): array;

    public function retrieveMedia(string $sessionId, string $mediaId, string $nonce, int $timestamp): array;
}

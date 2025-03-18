<?php

declare(strict_types=1);

namespace Application\Yoti;

use Application\Enums\IdMethod;
use Application\Model\Entity\CaseData;
use Application\Model\Entity\CounterService;
use Application\Yoti\Http\Exception\YotiException;
use Application\Helpers\CaseOutcomeCalculator;
use DateTime;
use DateTimeImmutable;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Clock\ClockInterface;
use Ramsey\Uuid\Uuid;

class SessionStatusService
{
    public function __construct(
        private readonly YotiServiceInterface $yotiService,
        private readonly CaseOutcomeCalculator $caseOutcomeCalculator,
        private readonly LoggerInterface $logger,
        private readonly ClockInterface $clock,
    ) {
    }

    public function getSessionStatus(CaseData $caseData): ?CounterService
    {
        if ($caseData->counterService !== null) {
            $currentNotificationStatus = $caseData->counterService->notificationState;

            if (
                $currentNotificationStatus === 'session_completion'
                && $caseData->counterService->state !== 'COMPLETED'
            ) {
                return $this->handleSessionCompletion($caseData);
            }
        }

        return $caseData->counterService;
    }

    /**
     * @psalm-suppress PossiblyNullPropertyAssignment
     */
    private function handleSessionCompletion(CaseData $caseData): ?CounterService
    {
        $nonce = strval(Uuid::uuid4());
        $timestamp = $this->clock->now()->getTimestamp();

        try {
            $response = $this->yotiService->retrieveResults($caseData->yotiSessionId, $nonce, $timestamp);
            $mediaId = null;
            $state = $response['state'];
            if (isset($response["resources"]["applicant_profiles"])) {
                $mediaId = $response['resources']['applicant_profiles'][0]['media']['id'];
            }
            $finalResult = $this->evaluateFinalResult($response['checks'], $mediaId, $caseData);

            $caseData->counterService->state = $state;
            $caseData->counterService->result = $finalResult;
            $caseData->identityCheckPassed = $finalResult;

            //TODO: does this actually work?
            if (isset($response['resources']['id_documents'][0]['created_at'])) {
                $createdAt = DateTimeImmutable::createFromFormat(
                    'Y-m-d\TH:i:s\Z',
                    $response['resources']['id_documents'][0]['created_at']
                );
            }
            $this->caseOutcomeCalculator->updateSendIdentityCheck($caseData, $createdAt ?? null);
        } catch (YotiException $e) {
            $this->logger->error('Yoti result error: ' . $e->getMessage());
        } catch (InvalidArgumentException $exception) {
            $this->logger->error('Error updating counterService results: ' . $exception->getMessage());
        }

        return $caseData->counterService;
    }

    private function getDocumentScanned(string $mediaId, string $yotiSessionId): array
    {
        $nonce = strval(Uuid::uuid4());
        $timestamp = (new DateTime())->getTimestamp();
        $result = [];

        try {
            $result = $this->yotiService->retrieveMedia($yotiSessionId, $mediaId, $nonce, $timestamp);
        } catch (YotiException $e) {
            $this->logger->error('Yoti media result error: ' . $e->getMessage());
        } catch (InvalidArgumentException $exception) {
            $this->logger->error('Error retreiving media results: ' . $exception->getMessage());
        }

        return $result["response"];
    }

    private function evaluateFinalResult(array $checks, ?string $mediaId, CaseData $caseData): bool
    {
        //If UK passport ensure document presented was in date range
        /**
         * @psalm-suppress PossiblyNullPropertyFetch
         */
        if (
            is_string($mediaId) &&
            $caseData->idMethodIncludingNation->id_method === IdMethod::PassportNumber->value &&
            $caseData->idMethodIncludingNation->id_country === "GBR"
        ) {
            $documentScanned = $this->getDocumentScanned($mediaId, $caseData->yotiSessionId);

            if (is_string($documentScanned["expiration_date"])) {
                $expiry = new DateTime($documentScanned["expiration_date"]);

                $acceptDate = (new DateTime())->modify('-18 months');

                if ($expiry < $acceptDate) {
                    $this->logger->info('sessionStatus: Passport 18 months+ expiry', ['caseId' => $caseData->id]);
                    return false;
                }
            }
        }

        foreach ($checks as $check) {
            if ($check['report']['recommendation']['value'] !== 'APPROVE') {
                return false;
            }
        }

        return true;
    }
}

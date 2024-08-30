<?php

declare(strict_types=1);

namespace Application\Yoti;

use Application\Fixtures\DataWriteHandler;
use Application\Model\Entity\CaseData;
use Application\Model\Entity\CounterService;
use Application\Sirius\EventSender;
use Application\Yoti\Http\Exception\YotiException;
use DateTime;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

class SessionStatusService
{
    public function __construct(
        private readonly YotiServiceInterface $yotiService,
        private readonly DataWriteHandler $dataImportHandler,
        private readonly LoggerInterface $logger,
        private readonly EventSender $eventSender,
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
     * @psalm-suppress PossiblyUndefinedArrayOffset
     * @psalm-suppress PossiblyNullArrayAccess
     */
    private function handleSessionCompletion(CaseData $caseData): ?CounterService
    {
        $nonce = strval(Uuid::uuid4());
        $timestamp = (new DateTime())->getTimestamp();

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

            $this->dataImportHandler->insertUpdateData($caseData);

            //add to logs until we can send status updates directly to Sirius
            $this->logger->info("Update for CaseId " . $caseData->id . "- State: $state, Result: " . $finalResult);

            $this->eventSender->send("identity-check-resolved", [
                "reference" => "opg:" . $caseData->id,
                "actorType" => $caseData->personType,
                "lpaIds" => $caseData->lpas,
                "time" => $response['resources']['id_documents'][0]['created_at'] ?? (new DateTime())->format('c'),
                "outcome" => $finalResult ? 'success' : 'failure',
            ]);
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
        if (is_string($mediaId) && $caseData->idMethod === "po_ukp") {
            $documentScanned = $this->getDocumentScanned($mediaId, $caseData->yotiSessionId);

            if (is_string($documentScanned["expiration_date"])) {
                $expiry = new DateTime($documentScanned["expiration_date"]);

                $currentDate = new DateTime();
                $acceptDate = (clone $currentDate)->modify('-18 months');

                if ($expiry < $acceptDate) {
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

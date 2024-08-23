<?php

declare(strict_types=1);

namespace Application\Yoti;

use Application\Fixtures\DataWriteHandler;
use Application\Model\Entity\CaseData;
use Application\Model\Entity\CounterService;
use Application\Yoti\Http\Exception\YotiException;
use DateTime;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

class SessionStatusService
{
    public function __construct(
        public readonly YotiServiceInterface $yotiService,
        public readonly DataWriteHandler $dataImportHandler,
        private readonly LoggerInterface $logger,
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
        $timestamp = (new DateTime())->getTimestamp();

        try {
            $response = $this->yotiService->retrieveResults($caseData->yotiSessionId, $nonce, $timestamp);
            $state = $response['state'];
            $finalResult = $this->evaluateFinalResult($response['checks']);

            $caseData->counterService->state = $state;
            $caseData->counterService->result = $finalResult;

            $this->dataImportHandler->insertUpdateData($caseData);

            //add to logs until we can send status updates directly to Sirius
            $this->logger->info("Update for CaseId " . $caseData->id . "- State: $state, Result: " . $finalResult);
        } catch (YotiException $e) {
            $this->logger->error('Yoti result error: ' . $e->getMessage());
        } catch (InvalidArgumentException $exception) {
            $this->logger->error('Error updating counterService results: ' . $exception->getMessage());
        }

        return $caseData->counterService;
    }

    private function evaluateFinalResult(array $checks): bool
    {
        foreach ($checks as $check) {
            if ($check['report']['recommendation']['value'] !== 'APPROVE') {
                return false;
            }
        }
        return true;
    }
}

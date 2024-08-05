<?php

declare(strict_types=1);

namespace Application\Yoti;

use Application\Fixtures\DataImportHandler;
use Application\Model\Entity\CaseData;
use Application\Model\Entity\CounterService;
use Application\Yoti\Http\Exception\YotiException;
use DateTime;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

class SessionStatusService
{
    public function __construct(
        public readonly YotiService $yotiService,
        public readonly DataImportHandler $dataImportHandler
    ) {
    }

    public function getSessionStatus(CaseData $caseData): string|array|CounterService
    {
        if ($caseData->counterService === null) {
            return "Error: counterService is null";
        }
        $currentNotificationStatus = $caseData->counterService->notificationState;

        if ($currentNotificationStatus === 'first_branch_visit') {
            return 'In Progress';
        }

        if ($currentNotificationStatus === 'session_completion' && $caseData->counterService->state !== 'COMPLETED') {
            return $this->handleSessionCompletion($caseData);
        }

        return $caseData->counterService;
    }

    private function handleSessionCompletion(CaseData $caseData): mixed
    {
        $nonce = strval(Uuid::uuid4());
        $timestamp = (new DateTime())->getTimestamp();
        $state = '';
        $finalResult = false;

        try {
            $response = $this->yotiService->retrieveResults($caseData->yotiSessionId, $nonce, $timestamp);
            $state = $response['results']['state'];
            $finalResult = $this->evaluateFinalResult($response['results']['checks']);

            $this->dataImportHandler->updateCaseChildAttribute(
                $caseData->id,
                'counterService.state',
                'S',
                $state,
            );
            $this->dataImportHandler->updateCaseChildAttribute(
                $caseData->id,
                'counterService.result',
                'S',
                $finalResult,
            );
        } catch (YotiException $e) {
            return 'Error: ' . $e->getMessage();
        } catch (InvalidArgumentException $exception) {
            return [
                'state' => $state,
                'result' => $finalResult,
                'error' => $exception->getMessage()
            ];
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

<?php

declare(strict_types=1);

namespace Application\Yoti;

use Application\Fixtures\DataImportHandler;
use Application\Fixtures\DataQueryHandler;
use Application\Model\Entity\CaseData;
use Application\Model\Entity\CounterService;
use Application\Yoti\Http\Exception\YotiException;
use DateTime;
use InvalidArgumentException;
use Laminas\Http\Response;
use Ramsey\Uuid\Uuid;

class SessionStatusService
{
    public function __construct(
        public readonly YotiService $yotiService,
        public readonly DataQueryHandler $queryHandler,
        public readonly DataImportHandler $dataImportHandler
    ) {
    }

    public function getSessionStatus(string $uuid): string|array|CounterService
    {
        //if no notifications do one call to yoti just for status which will most likely just be ONGOING
        $caseData = $this->queryHandler->getCaseByUUID($uuid);
        $currentNotificationStatus = $caseData->counterService->notificationState;

        if ($currentNotificationStatus == 'first_branch_visit') {
            return 'In Progress';
        }
        //if notify sent with session_completion then we do
        if ($currentNotificationStatus == 'session_completion' && $caseData->counterService->state !== 'COMPLETED') {
            //get actual result from yoti and update case accordingly
            $nonce = strval(Uuid::uuid4());
            $dateTime = new DateTime();
            $timestamp = $dateTime->getTimestamp();
            try {
                $response = $this->yotiService->retrieveResults($caseData->yotiSessionId, $nonce, $timestamp);
                $finalResult = true;
                $state = $response['results']['state'];
                $checks = $response['results']['checks'];
                $statusWithErrors = [];

                foreach ($checks as $check) {
                    if ($check['report']['recommendation']['value'] == 'APPROVE') {
                        $finalResult = false;
                    }
                }
                //now save the save bac to case
                try {
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
                } catch (InvalidArgumentException $exception) {
                    $statusWithErrors['state'] = $state;
                    $statusWithErrors['result'] = $finalResult;
                    $statusWithErrors['error'] = $exception->getMessage();

                    return $statusWithErrors;
                }

            } catch (YotiException $e) {
                return 'Error: ' . $e->getMessage();
            }

        }
        return $caseData->counterService;
    }
}

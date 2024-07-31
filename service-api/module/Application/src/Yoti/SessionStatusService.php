<?php

declare(strict_types=1);

namespace Application\Yoti;


use Application\Fixtures\DataQueryHandler;
use Application\Model\Entity\CaseData;
use Application\Yoti\Http\Exception\YotiException;
use DateTime;
use Laminas\Http\Response;
use Ramsey\Uuid\Uuid;

class SessionStatusService
{
    public function __construct(
        public readonly YotiService $yotiService,
        public readonly DataQueryHandler $queryHandler,

    ) {
    }

    public function getSessionStatus(string $uuid): string
    {
        //if no notifications do one call to yoti just for status which will most likely just be ONGOING
        $caseData = $this->queryHandler->getCaseByUUID($uuid);
        $currentNotificationStatus = $caseData->counterService->notificationState;

        if ($currentNotificationStatus == 'first_branch_visit' || $caseData->state == 'ONGOING') {
            return 'In Progress';
        }
        //if notify sent with session_completion then we do
        if ($currentNotificationStatus == 'session_completion' && $caseData->state !== 'COMPLETED') {
            //get actual result from yoti and update case accordingly
            $nonce = strval(Uuid::uuid4());
            $dateTime = new DateTime();
            $timestamp = $dateTime->getTimestamp();
            try {
                $response = $this->yotiService->retrieveResults($caseData->sessionId, $nonce, $timestamp);
                $finalResult = true;
                $state = $response['results']['state'];
                $checks = $response['results']['checks'];

                foreach ($checks as $check) {
                    if ($check['report']['recommendation']['value'] == 'APPROVE') {
                        $finalResult = false;
                    }
                }

                //now save the save bac to case



            } catch (YotiException $e) {
                return 'Error: ' . $e->getMessage();
            }


        }
    }
}

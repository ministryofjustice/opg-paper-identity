<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Model\Entity\CaseData;
use Application\Fixtures\DataWriteHandler;
use Application\Sirius\EventSender;
use Application\Sirius\UpdateStatus;
use Psr\Log\LoggerInterface;

class CaseOutcomeCalculator
{
    public function __construct(
        private readonly DataWriteHandler $dataHandler,
        private readonly LoggerInterface $logger,
        private readonly EventSender $eventSender
    ) {
    }

    public function updateSendIdentityCheck(CaseData $caseData, string $time): void
    {
        $this->dataHandler->insertUpdateData($caseData);

        // add to logs
        $this->logger->info(
            "Update for CaseId " . $caseData->id . "- Result: " .
            ($caseData->identityCheckPassed ? 'Passed' : 'Failed')
        );

        $this->eventSender->send("identity-check-updated", [
            "reference" => "opg:" . $caseData->id,
            "actorType" => $caseData->personType,
            "lpaUids" => $caseData->lpas,
            "time" => $time,
            "state" => ($caseData->identityCheckPassed ? UpdateStatus::Success : UpdateStatus::Failure)->value,
        ]);

        $this->dataHandler->setTTL($caseData->id);
    }
}

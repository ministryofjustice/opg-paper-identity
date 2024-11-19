<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Model\Entity\CaseData;
use Application\Fixtures\DataWriteHandler;
use Application\Sirius\EventSender;
use Psr\Log\LoggerInterface;

class CaseOutcomeCalculator
{
    public function __construct(
        private readonly DataWriteHandler $dataHandler,
        private readonly LoggerInterface $logger,
        private readonly EventSender $eventSender,
    ) {
    }

    public function updateSendIdentityCheck(CaseData $caseData, string $time): void
    {
        $this->dataHandler->insertUpdateData($caseData);

        // add to logs
        $this->logger->info("Update for CaseId " . $caseData->id . "- Result: " . $caseData->identityCheckPassed);

        $this->eventSender->send("identity-check-resolved", [
            "reference" => "opg:" . $caseData->id,
            "actorType" => $caseData->personType,
            "lpaIds" => $caseData->lpas,
            "time" => $time,
            "outcome" => $caseData->identityCheckPassed ? 'success' : 'failure',
        ]);
    }

    public function updateFinaliseIdentityCheck(CaseData $caseData, string $time): void
    {
        // add to logs
        $this->logger->info("Update for CaseId " .
            $caseData->id . "- Result: " . json_encode($caseData->caseAssistance));

        $caseData->caseAssistance &&
        $this->eventSender->send("identity-check-assistance", [
            "reference" => "opg:" . $caseData->id,
            "actorType" => $caseData->personType,
            "lpaIds" => $caseData->lpas,
            "time" => $time,
            "assistance" => $caseData->caseAssistance->assistance,
            "assistance_details" => $caseData->caseAssistance->details
        ]);
    }
}

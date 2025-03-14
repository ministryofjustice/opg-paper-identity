<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Model\Entity\CaseData;
use Application\Exceptions\InvalidIdMethod;
use Application\Fixtures\DataWriteHandler;
use Application\Sirius\EventSender;
use Application\Sirius\UpdateStatus;
use Psr\Log\LoggerInterface;
use Psr\Clock\ClockInterface;
use DateTimeImmutable;

class CaseOutcomeCalculator
{
    public function __construct(
        private readonly DataWriteHandler $dataHandler,
        private readonly LoggerInterface $logger,
        private readonly EventSender $eventSender,
        private readonly ClockInterface $clock,
    ) {
    }

    public function calculateStatus(CaseData $caseData): UpdateStatus
    {
        // QUESTION: do we always choose abandonFlow if it exists??
        // What if they already have a result pass/fail or have chosen PO/CPR/VOUCH...
        if (isset($caseData->caseProgress->abandonedFlow)) {
            return UpdateStatus::Exit;
        }

        $routeToStatus = [
            'OnBehalf' => UpdateStatus::VouchStarted,
            'cpr' => UpdateStatus::CopStarted,
            'POST_OFFICE' => UpdateStatus::CounterServiceStarted,
            'TELEPHONE' => $caseData->identityCheckPassed ? UpdateStatus::Success : UpdateStatus::Failure
        ];

        if (is_null($caseData->idRoute)) {
            throw new InvalidIdMethod("id-route not set: {$caseData->id}");
        }

        return $routeToStatus[$caseData->idRoute];
    }

    // QUESTION: do we need to have the time param OR could we just just now - so TTL and time on the event would always
    // match.
    // we only pass time in counter-service and i dont fully understand where that is coming from...
    public function updateSendIdentityCheck(CaseData $caseData, ?DateTimeImmutable $timestamp = null): void
    {
        if (is_null($timestamp)) {
            $timestamp = $this->clock->now();
        }
        // Question - whats the difference between the `insertUpdateData` and `updateCaseData` methods?
        // can we rationalise between the two?
        $this->dataHandler->insertUpdateData($caseData);
        $state = $this->calculateStatus($caseData)->value;
        $this->logger->info("Sending identity check to sirius for CaseId: {$caseData->id} - Status: {$state}");

        $this->eventSender->send("identity-check-updated", [
            "reference" => "opg:" . $caseData->id,
            "actorType" => $caseData->personType,
            "lpaUids" => $caseData->lpas,
            "time" => $timestamp->format('c'),
            "state" => $state,
        ]);

        $this->dataHandler->setTTL($caseData->id, $timestamp);
    }
}

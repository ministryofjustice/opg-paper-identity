<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Model\Entity\CaseData;
use Application\Exceptions\IdMethodNotSet;
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
            throw new IdMethodNotSet("id-route not set: {$caseData->id}");
        }

        return $routeToStatus[$caseData->idRoute];
    }

    public function updateSendIdentityCheck(CaseData $caseData, ?DateTimeImmutable $timestamp = null): void
    {
        if (is_null($timestamp)) {
            $timestamp = $this->clock->now();
        }

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

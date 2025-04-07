<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Model\Entity\CaseData;
use Application\Enums\IdRoute;
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
            IdRoute::VOUCHING->value => UpdateStatus::VouchStarted,
            IdRoute::COURT_OF_PROTECTION->value => UpdateStatus::CopStarted,
            IdRoute::POST_OFFICE->value => UpdateStatus::CounterServiceStarted,
            IdRoute::KBV->value => $caseData->identityCheckPassed ? UpdateStatus::Success : UpdateStatus::Failure
        ];

        if (is_null($caseData->idMethod)) {
            throw new IdMethodNotSet("idMethod not set: {$caseData->id}");
        }

        return $routeToStatus[$caseData->idMethod->idRoute];
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

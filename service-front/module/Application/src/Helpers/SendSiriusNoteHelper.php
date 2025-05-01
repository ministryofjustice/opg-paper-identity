<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Services\SiriusApiService;
use Laminas\HTTP\Request;
use Psr\Log\LoggerInterface;

class SendSiriusNoteHelper {

    public function __construct(
        private readonly SiriusApiService $siriusApiService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function sendAbandonFlowNote(string $reason, string $notes, array $lpas, Request $request): void
    {
        $this->logger->info("in sendAbandonFlowNote");

        $verboseReason = match ($reason) {
            'cd' => 'Call dropped',
            'nc' => 'Caller not able to complete at this time',
            'ot' => 'Other',
            default => 'Unknown'
        };

        $noteDescription = "Reason: " . $verboseReason;
        $noteDescription .= "\n\n" . $notes;

        foreach ($lpas as $lpaUid) {
            $this->siriusApiService->addNote(
                $request,
                $lpaUid,
                "ID Check Abandoned",
                "ID Check Incomplete",
                $noteDescription
            );
        }
    }

    public function sendBlockedRoutesNote(array $detailsData, Request $request): void
    {
        $withVouchingMessage =
            "The donor on the LPA has tried and failed to ID over the phone. " .
            "This donor can use the Post Office, choose someone to vouch for them " .
            "or ask the Court of Protection to register their LPA.";

        $noVouchingMessage =
            "The donor on the LPA has tried and failed to ID over the phone. " .
            "This donor can use the Post Office to ID or ask the Court of Protection to register their LPA. " .
            "They cannot use the vouching route to ID.";

        $personType = $detailsData['personType'];
        $docCheck = $detailsData['caseProgress']['docCheck']['state'] ?? null;
        $fraud = $detailsData['caseProgress']['fraudScore']['decision'] ?? null;
        $kbvs = $detailsData['caseProgress']['kbvs']['result'] ?? null;

        $this->logger->info("in sendBlockedRoutesNote");

        $description = null;
        if ( $personType === 'donor') {
            if ($docCheck === false) {
                $description = $noVouchingMessage;
            } elseif ($docCheck === true) {
                if ($kbvs !== true) {
                    if (in_array($fraud, ['ACCEPT', 'CONTINUE', 'NODECISION'])) {
                        $description = $withVouchingMessage;
                    } else {
                        $description = $noVouchingMessage;
                    }
                }
            }
        }

        if (! is_null($description)) {
            $this->logger->info("sending note to sirius: {$description}");
            foreach ($detailsData['lpas'] as $lpa) {
                $this->siriusApiService->addNote(
                    $request,
                    $lpa,
                    "ID check failed over the phone",
                    "ID Check Incomplete",
                    $description
                );
            }
        }
    }
}

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

        $noteDescription = "Reason: " . $reason;
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
        $personType = $detailsData['personType'];
        $kbvs = $detailsData['caseProgress']['kbvs'] ?? null;

        $this->logger->info("in sendBlockedRoutesNote");

        if ( $personType== 'donor' && ! is_null($kbvs) && $kbvs['result'] !== true ) {
            $description = match ($detailsData["caseProgress"]["fraudScore"]["decision"]) {
                "ACCEPT", "CONTINUE" =>
                    "The donor on the LPA has tried and failed to ID over the phone. " .
                    "This donor can use the Post Office, choose someone to vouch for them " .
                    "or ask the Court of Protection to register their LPA.",
                default =>
                    "The donor on the LPA has tried and failed to ID over the phone. " .
                    "This donor can use the Post Office to ID or ask the Court of Protection to register their LPA. " .
                    "They cannot use the vouching route to ID."
            };

            $this->logger->info("sending note to sirius");
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
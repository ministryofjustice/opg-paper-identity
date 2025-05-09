<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Services\SiriusApiService;
use Laminas\Http\Request;
use Psr\Log\LoggerInterface;

class SendSiriusNoteHelper
{
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
        $personTypeLkup = [
            'certificateProvider' => 'certificate provider',
            'voucher' => 'person vouching',
            'donor' => null,
        ];

        $personType = $detailsData['personType'];
        $name = $detailsData['firstName'] . " " . $detailsData['lastName'];
        $docCheck = $detailsData['caseProgress']['docCheck']['state'] ?? null;
        $fraud = $detailsData['caseProgress']['fraudScore']['decision'] ?? null;
        $kbvs = $detailsData['caseProgress']['kbvs']['result'] ?? null;

        $donorWithVouchingMessage =
            "The donor on the LPA has tried and failed to ID over the phone. " .
            "This donor can use the Post Office, choose someone to vouch for them " .
            "or ask the Court of Protection to register their LPA.";

        $donorNoVouchingMessage =
            "The donor on the LPA has tried and failed to ID over the phone. " .
            "This donor can use the Post Office to ID or ask the Court of Protection to register their LPA. " .
            "They cannot use the vouching route to ID.";

        $nonDonorMessage =
            "The $personTypeLkup[$personType] ($name) has failed to ID over the phone. " .
            "This person can only use the Post Office to ID";

        $description = null;

        if ($docCheck === false) {
            $description = $personType === 'donor' ? $donorNoVouchingMessage : $nonDonorMessage;
        } elseif ($docCheck === true && $kbvs !== true) {
            if ($personType === 'donor') {
                if (in_array($fraud, ['ACCEPT', 'CONTINUE', 'NODECISION'])) {
                    $description = $donorWithVouchingMessage;
                } else {
                    $description = $donorNoVouchingMessage;
                }
            } else {
                $description = $nonDonorMessage;
            }
        }

        if (! is_null($description)) {
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

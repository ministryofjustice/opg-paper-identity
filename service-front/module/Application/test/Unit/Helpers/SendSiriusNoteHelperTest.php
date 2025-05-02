<?php

declare(strict_types=1);

namespace ApplicationTest\Unit\Helpers;

use Application\Helpers\SendSiriusNoteHelper;
use Application\Services\SiriusApiService;
use Laminas\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SendSiriusNoteHelperTest extends TestCase
{
    private SiriusApiService&MockObject $siriusApiServiceMock;
    private LoggerInterface&MockObject $loggerMock;
    private SendSiriusNoteHelper $helper;

    protected function setUp(): void
    {
        $this->siriusApiServiceMock = $this->createMock(SiriusApiService::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->helper = new SendSiriusNoteHelper(
            $this->siriusApiServiceMock,
            $this->loggerMock
        );
    }

    #[DataProvider('sendAbandonFlowNoteData')]
    public function testSendAbandonFlowNote(string $reason, string $verboseReason): void
    {
        $request = $this->createMock(Request::class);

        $lpas = ['lpaOne', 'lpaTwo'];

        $notes = "these are some notes";
        $expectedDescription = "$verboseReason\n\n$notes";

        $expectedArgs = [
            [$request, 'lpaOne', "ID Check Abandoned", "ID Check Incomplete", $expectedDescription,],
            [$request, 'lpaTwo', "ID Check Abandoned", "ID Check Incomplete", $expectedDescription,],
        ];

        $this
            ->siriusApiServiceMock
            ->expects($this->exactly(2))
            ->method('addNote')
            ->willReturnCallback(function (mixed ...$args) use (&$expectedArgs) {
                $expected = array_shift($expectedArgs);
                $this->assertEquals($expected, $args);
            });

        $this->helper->sendAbandonFlowNote($reason, $notes, $lpas, $request);
    }

    public static function sendAbandonFlowNoteData(): array
    {
        return [
            ['cd', 'Reason: Call dropped'],
            ['nc', 'Reason: Caller not able to complete at this time'],
            ['ot', 'Reason: Other'],
            ['xx', 'Reason: Unknown'],
        ];
    }

    #[DataProvider('sendBlockedRoutesNoteData')]
    public function testSendBlockedRoutesNote(
        string $personType,
        ?bool $docCheck,
        ?string $fraudOutcome,
        ?bool $kbvs,
        ?string $expectedDescription
    ): void {
        $request = $this->createMock(Request::class);

        $docCheck = is_null($docCheck) ? null : ['state' => $docCheck];
        $fraudOutcome = is_null($fraudOutcome) ? null : ['decision' => $fraudOutcome];
        $kbvs = is_null($kbvs) ? null : ['result' => $kbvs];

        $detailsData = [
            'firstName' => 'Lee',
            'lastName' => 'Manthrope',
            'personType' => $personType,
            'caseProgress' => [
                'fraudScore' => $fraudOutcome,
                'kbvs' => $kbvs,
                'docCheck' => $docCheck,
            ],
            'lpas' => ['lpa-one', 'lpa-two']
        ];

        if (! is_null($expectedDescription)) {
            $this
                ->siriusApiServiceMock
                ->expects($this->exactly(2))
                ->method('addNote')
                ->with(
                    $this->anything(),
                    $this->anything(),
                    "ID check failed over the phone",
                    "ID Check Incomplete",
                    $this->stringContains($expectedDescription),
                );
        } else {
            $this
            ->siriusApiServiceMock
            ->expects($this->never())
            ->method('addNote');
        }

        $this->helper->sendBlockedRoutesNote($detailsData, $request);
    }

    public static function sendBlockedRoutesNoteData(): array
    {
        $withVouchingNote = 'choose someone to vouch for them';
        $noVouchingNote = 'They cannot use the vouching route to ID.';

        $testCases = [
            'donor with no docCheck, fraud or kbvs' => ['donor', null, null, null, null],
            'donor failed docCheck' => ['donor', false, null, null, $noVouchingNote,],
             // TODO: what do we actually want to happen?
            'donor with a NODECISION fraud result' => ['donor', true, 'NODECISION', null, $withVouchingNote],
        ];

        foreach (['ACCEPT', 'CONTINUE'] as $resp) {
            $testCases["donor passed fraud with {$resp} and abandoned kbvs"] = [
                'donor', true, $resp, null, $withVouchingNote,
            ];
            $testCases["donor passed fraud with {$resp} and passed kbvs"] = ['donor', true, $resp, true, null,];
            $testCases["donor passed fraud with {$resp} and failed kbvs"] = [
                'donor', true, $resp, false, $withVouchingNote,
            ];
        }

        foreach (['STOP', 'REFER'] as $resp) {
            $testCases["donor failed fraud with {$resp} and abandoned kbvs"] = [
                'donor', true, $resp, null, $noVouchingNote,
            ];
            $testCases["donor failed fraud with {$resp} and passed kbvs"] = ['donor', true, $resp, true, null,];
            $testCases["donor failed fraud with {$resp} and failed kbvs"] = [
                'donor', true, $resp, false, $noVouchingNote,
            ];
        }

        $msgLkup = [
            'certificateProvider' => 'The certificate provider (Lee Manthrope) has failed to ID over the phone.',
            'voucher' => 'The person vouching (Lee Manthrope) has failed to ID over the phone.'
        ];

        foreach (['certificateProvider', 'voucher'] as $personType) {
            $testCases["$personType with failed docCheck"] = [$personType, false, null, null, $msgLkup[$personType]];
            $testCases["$personType passed kbvs"] = [$personType, true, 'ACCEPT', true, null];
            $testCases["$personType abandoned kbvs"] = [$personType, true, 'ACCEPT', null, $msgLkup[$personType]];
            $testCases["$personType failed kbvs"] = [$personType, true, 'ACCEPT', false, $msgLkup[$personType]];
        }

        return $testCases;
    }
}

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

    public function testSendAbandonFlowNote(): void
    {
        $request = $this->createMock(Request::class);

        $lpas = ['lpaOne', 'lpaTwo'];

        $reason = "cd";
        $notes = "these are some notes";
        $expectedDescription = "Reason: Call dropped\n\nthese are some notes";

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
            'not donor so no note' => ['voucher', true, 'ACCEPT', null, null,],
            'donor with no docCheck, fraud or kbvs' => ['donor', null, null, null, null],
            'donor failed docCheck' => ['donor', false, null, null, $noVouchingNote,],
             // TODO: what do we actually want to happen?
            'donor with a NODECISION fraud result' => ['donor', true, 'NODECISION', null, $withVouchingNote]
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

        return $testCases;
    }
}

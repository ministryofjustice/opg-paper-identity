<?php

declare(strict_types=1);

namespace ApplicationTest\Unit\Sirius;

use Application\Sirius\EventSender;
use Aws\EventBridge\EventBridgeClient;
use PHPUnit\Framework\TestCase;

class EventSenderTest extends TestCase
{
    public function testSendsEvents(): void
    {
        $eventBridgeMock = $this->createMock(EventBridgeClient::class);

        $sut = new EventSender($eventBridgeMock, 'my-event-bus');

        $eventBridgeMock->expects($this->once())
            ->method('__call')
            ->with('putEvents', [
                [
                    'Entries' => [
                        [
                            'Source' => 'opg.poas.identity-check',
                            'EventBusName' => 'my-event-bus',
                            'DetailType' => 'event-type',
                            'Detail' => '{"key":"value"}',
                        ],
                    ],
                ],
            ]);

        $sut->send('event-type', [
            'key' => 'value',
        ]);
    }
}

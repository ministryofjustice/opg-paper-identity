<?php

declare(strict_types=1);

namespace Application\Sirius;

use Aws\EventBridge\EventBridgeClient;

class EventSender
{
    public function __construct(
        private readonly EventBridgeClient $eventBridgeClient,
        private readonly string $eventBusName,
    ) {
    }

    /**
     * @param string $detailType
     * @param array<mixed> $detail
     */
    public function send(string $detailType, array $detail): void
    {
        $this->eventBridgeClient->putEvents([
            'Entries' => [
                [
                    'Source' => 'opg.poas.identity-check',
                    'EventBusName' => $this->eventBusName,
                    'DetailType' => $detailType,
                    'Detail' => json_encode($detail),
                ],
            ],
        ]);
    }
}

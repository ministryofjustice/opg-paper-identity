<?php

declare(strict_types=1);

namespace Application\Helpers;

use Application\Fixtures\DataWriteHandler;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;

class ExpireCase
{
    public function __construct(
        private readonly DataWriteHandler $dataHandler,
        private readonly LoggerInterface $logger,
        private readonly ClockInterface $clock,
    ) {
    }

    public function setCaseExpiry(string $uuid): void
    {

        $ttlDays = getenv("AWS_DYNAMODB_TTL_DAYS");
        $ttl = $this->clock->now()->modify("+{$ttlDays} days");

        echo("---------------------------------------------------------------");
        echo($ttlDays);
        echo("---------------------------------------------------------------");

        $this->logger->info("Setting case {$uuid} to expire after {$ttl->format('c')}");
        $this->dataHandler->setTTL($uuid, $ttl->format('U'));
    }
}

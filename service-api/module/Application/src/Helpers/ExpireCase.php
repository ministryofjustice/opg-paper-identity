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

        $TTL_DAYS = getenv("AWS_DYNAMODB_TTL_DAYS");
        $ttl = $this->clock->now()->modify("+{$TTL_DAYS} days");

        $this->logger->info("Setting case {$uuid} to expire in {$TTL_DAYS} days");
        $this->dataHandler->setTTL($uuid, $ttl->format('U'));
    }
}

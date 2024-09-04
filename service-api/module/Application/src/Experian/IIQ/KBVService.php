<?php

declare(strict_types=1);

namespace Application\Experian\IIQ;

use Application\KBV\KBVServiceInterface;
use Application\Mock\KBV\KBVService as MockKBVService;
use Psr\Log\LoggerInterface;

class KBVService implements KBVServiceInterface
{
    public function __construct(
        private readonly WaspService $authService,
        private readonly LoggerInterface $logger,
        private readonly MockKBVService $mockKbvService,
    ) {
    }

    public function fetchFormattedQuestions(string $uuid): array
    {
        $token = $this->authService->loginWithCertificate();

        $this->logger->info(sprintf('Token length %s', strlen($token)));

        return $this->mockKbvService->fetchFormattedQuestions($uuid);
    }
}

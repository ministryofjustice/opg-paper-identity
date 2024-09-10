<?php

declare(strict_types=1);

namespace Application\Experian\IIQ;

use Application\KBV\KBVServiceInterface;
use Application\Mock\KBV\KBVService as MockKBVService;
use Psr\Log\LoggerInterface;

class KBVService implements KBVServiceInterface
{
    public function __construct(
        private readonly IIQService $authService,
        private readonly LoggerInterface $logger,
        private readonly MockKBVService $mockKbvService,
    ) {
    }

    public function fetchFormattedQuestions(string $uuid): array
    {
        $questions = $this->authService->startAuthenticationAttempt();

        $this->logger->info(sprintf('Found %d questions', count($questions)));

        return $this->mockKbvService->fetchFormattedQuestions($uuid);
    }
}

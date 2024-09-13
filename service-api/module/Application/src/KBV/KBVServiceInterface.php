<?php

declare(strict_types=1);

namespace Application\KBV;

/**
 * @psalm-type Question = array{
 *   number: string,
 *   experianId: string,
 *   question: string,
 *   prompts: string[],
 * }
 */
interface KBVServiceInterface
{
    /**
     * Retrieves an array containing questions without answers and formatted questions with answers.
     *
     * @return Question[]
     */
    public function fetchFormattedQuestions(string $uuid): array;
}

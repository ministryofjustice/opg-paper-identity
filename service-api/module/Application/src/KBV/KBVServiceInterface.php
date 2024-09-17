<?php

declare(strict_types=1);

namespace Application\KBV;

/**
 * @psalm-type Question = array{
 *   externalId: string,
 *   question: string,
 *   prompts: string[],
 *   answered: bool,
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

    /**
     * @param array<string, string> $answers
     */
    public function checkAnswers(array $answers, string $uuid): AnswersOutcome;
}

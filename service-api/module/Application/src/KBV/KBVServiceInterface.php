<?php

declare(strict_types=1);

namespace Application\KBV;

use Application\Model\Entity\KBVQuestion;

interface KBVServiceInterface
{
    /**
     * Retrieves an array containing questions without answers and formatted questions with answers.
     *
     * @return KBVQuestion[]
     */
    public function fetchFormattedQuestions(string $uuid): array;

    /**
     * @param array<string, string> $answers
     */
    public function checkAnswers(array $answers, string $uuid): AnswersOutcome;
}

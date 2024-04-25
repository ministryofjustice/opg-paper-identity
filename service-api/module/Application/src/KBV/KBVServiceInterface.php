<?php

declare(strict_types=1);

namespace Application\KBV;

interface KBVServiceInterface
{
    /**
     * Retrieves an array containing questions without answers and formatted questions with answers.
     *
     * @return array Returns an associative array with two keys:
     *    - 'questionsWithoutAnswers': An array containing questions without answers, each question represented
     *       as an associative array with keys 'number', 'question', and 'prompts'.
     *    - 'formattedQuestions': An array containing formatted questions with answers, each question represented
     *       as an associative array with keys 'number', 'question', 'prompts', and 'answer'.
     */
    public function fetchFormattedQuestions(string $uuid): array;
}

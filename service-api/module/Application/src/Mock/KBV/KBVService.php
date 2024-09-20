<?php

declare(strict_types=1);

namespace Application\Mock\KBV;

use Application\KBV\AnswersOutcome;
use Application\KBV\KBVServiceInterface;
use Application\Model\Entity\KBVQuestion;

class KBVService implements KBVServiceInterface
{
    /**
     * @psalm-suppress ArgumentTypeCoercion
     * @return KBVQuestion[]
     */
    private function getKBVQuestions(): array
    {
        $questionsList = $this->questionsList();
        $questionSelection = [];

        foreach (array_rand($questionsList, 4) as $key) {
            $question = $questionsList[$key];
            shuffle($question['prompts']);
            $questionSelection[] = KBVQuestion::fromArray([
                'externalId' => $question['externalId'],
                'question' => $question['question'],
                'prompts' => $question['prompts'],
                'answered' => false,
            ]);
        }

        return $questionSelection;
    }

    public function fetchFormattedQuestions(string $uuid): array
    {
        $questions = $this->getKBVQuestions();

        return $questions;
    }

    public function checkAnswers(array $answers, string $uuid): AnswersOutcome
    {
        $db = $this->getKBVQuestions();

        foreach ($answers as $externalId => $answer) {
            $question = array_filter($db, fn ($q) => $q->externalId === $externalId)[0];

            if ($question->prompts[0] !== $answer) {
                return AnswersOutcome::CompleteFail;
            }
        }

        return AnswersOutcome::CompletePass;
    }

    private function questionsList(): array
    {
        return [
                [
                    'externalId' => 'MOCK-01',
                    'question' => 'Who is your electricity supplier?',
                    'prompts' => [
                        0 => 'VoltWave',
                        1 => 'Glow Electric',
                        2 => 'Powergrid Utilities',
                        3 => 'Bright Bristol Power',
                    ],
                ],
                [
                    'externalId' => 'MOCK-02',
                    'question' => 'How much was your last phone bill?',
                    'prompts' => [
                        0 => "£5.99",
                        1 => "£11",
                        2 => "£16.84",
                        3 => "£1.25",
                    ],
                ],
                [
                    'externalId' => 'MOCK-03',
                    'question' => "What is your mother’s maiden name?",
                    'prompts' => [
                        0 => 'Germanotta',
                        1 => 'Gumm',
                        2 => 'Micklewhite',
                        3 => 'Blythe',
                    ],
                ],
                [
                    'externalId' => 'MOCK-04',
                    'question' => 'What are the last two characters of your car number plate?',
                    'prompts' => [
                        0 => 'IF',
                        1 => 'SJ',
                        2 => 'WP',
                        3 => 'PG',
                    ],
                ],
                [
                    'externalId' => 'MOCK-05',
                    'question' => 'Name one of your current account providers',
                    'prompts' => [
                        0 => 'Liberty Trust Bank',
                        1 => 'Heritage Horizon Bank',
                        2 => 'Prosperity Peak Financial',
                        3 => 'Summit State Saving',
                    ],
                ],
                [
                    'externalId' => 'MOCK-06',
                    'question' => 'In what month did you move into your current house?',
                    'prompts' => [
                        0 => 'July',
                        1 => 'September',
                        2 => 'March',
                        3 => 'April',
                    ],
                ],
                [
                    'externalId' => 'MOCK-07',
                    'question' => 'Which company provides your car insurance?',
                    'prompts' => [
                        0 => 'SafeDrive Insurance',
                        1 => 'Guardian Drive Assurance',
                        2 => 'SheildSafe',
                        3 => 'Swift Cover Protection',
                    ],
                ],
                [
                    'externalId' => 'MOCK-08',
                    'question' => 'What colour is your front door?',
                    'prompts' => [
                        0 => 'Pink',
                        1 => 'Green',
                        2 => 'Black',
                        3 => 'Yellow',
                    ],
                ],
        ];
    }
}

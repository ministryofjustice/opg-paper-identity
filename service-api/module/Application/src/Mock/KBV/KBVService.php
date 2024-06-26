<?php

declare(strict_types=1);

namespace Application\Mock\KBV;

use Application\KBV\KBVServiceInterface;

class KBVService implements KBVServiceInterface
{
    /**
     * @psalm-suppress ArgumentTypeCoercion
     */
    private function getKBVQuestions(): array
    {
        $questionsList = $this->questionsList();
        $questionSelection = [];

        foreach (array_rand($questionsList, 4) as $key) {
            $question = $questionsList[$key];
            shuffle($question['prompts']);
            $questionSelection[] = $question;
        }

        return $questionSelection;
    }

    public function fetchFormattedQuestions(string $uuid): array
    {
        $questions = $this->getKBVQuestions();
        $questionsWithoutAnswers = [];
        //update formatting here to match FE expectations
        $formattedQuestions = [];
        $mapNumber = [
            '0' => 'one',
            '1' => 'two',
            '2' => 'three',
            '3' => 'four'
        ];
        for ($i = 0; $i < 4; $i++) {
            $question = $questions[$i];
            $number = $mapNumber[$i];
            $questionNumbered = array_merge(['number' => $number], $question);
            $formattedQuestions[$number] = $questionNumbered;
            unset($questionNumbered['answer']);
            $questionsWithoutAnswers[$number] = $questionNumbered;
        }

        return ['questionsWithoutAnswers' => $questionsWithoutAnswers, 'formattedQuestions' => $formattedQuestions];
    }

    private function questionsList(): array
    {
        return [
                0 => [
                    'question' => 'Who is your electricity supplier?',
                    'prompts' => [
                        0 => 'VoltWave',
                        1 => 'Glow Electric',
                        2 => 'Powergrid Utilities',
                        3 => 'Bright Bristol Power'
                    ],
                    'answer' => 'VoltWave'
                ],
                1 => [
                    'question' => 'How much was your last phone bill?',
                    'prompts' => [
                        0 => "£5.99",
                        1 => "£11",
                        2 => "£16.84",
                        3 => "£1.25"
                    ],
                    'answer' => "£5.99"
                ],
                2 => [
                    'question' => "What is your mother’s maiden name?",
                    'prompts' => [
                        0 => 'Germanotta',
                        1 => 'Gumm',
                        2 => 'Micklewhite',
                        3 => 'Blythe'
                    ],
                    'answer' => 'Germanotta'
                ],
                3 => [
                    'question' => 'What are the last two characters of your car number plate?',
                    'prompts' => [
                        0 => 'IF',
                        1 => 'SJ',
                        2 => 'WP',
                        3 => 'PG'
                    ],
                    'answer' => 'IF'
                ],
                4 => [
                    'question' => 'Name one of your current account providers',
                    'prompts' => [
                        0 => 'Liberty Trust Bank',
                        1 => 'Heritage Horizon Bank',
                        2 => 'Prosperity Peak Financial',
                        3 => 'Summit State Saving'
                    ],
                    'answer' => 'Liberty Trust Bank'
                ],
                5 => [
                    'question' => 'In what month did you move into your current house?',
                    'prompts' => [
                        0 => 'July',
                        1 => 'September',
                        2 => 'March',
                        3 => 'April'
                    ],
                    'answer' => 'July'
                ],
                6 => [
                    'question' => 'Which company provides your car insurance?',
                    'prompts' => [
                        0 => 'SafeDrive Insurance',
                        1 => 'Guardian Drive Assurance',
                        2 => 'SheildSafe',
                        3 => 'Swift Cover Protection'
                    ],
                    'answer' => 'SafeDrive Insurance'
                ],
                7 => [
                    'question' => 'What colour is your front door?',
                    'prompts' => [
                        0 => 'Pink',
                        1 => 'Green',
                        2 => 'Black',
                        3 => 'Yellow'
                    ],
                    'answer' => 'Pink'
                ],
        ];
    }
}

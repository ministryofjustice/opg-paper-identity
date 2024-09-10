<?php

declare(strict_types=1);

namespace Application\Experian\IIQ;

use Application\Fixtures\DataQueryHandler;
use Application\KBV\KBVServiceInterface;
use Psr\Log\LoggerInterface;

class KBVService implements KBVServiceInterface
{
    public function __construct(
        private readonly IIQService $authService,
        private readonly LoggerInterface $logger,
        private readonly DataQueryHandler $queryHandler
    ) {
    }

    /**
     * @param string $uuid
     * @return array[]
     * @throws Exception\CannotGetQuestionsException
     * @psalm-suppress PossiblyNullArgument
     * @psalm-suppress InvalidArrayOffset
     */
    public function fetchFormattedQuestions(string $uuid): array
    {
        $caseData = $this->queryHandler->getCaseByUUID($uuid);
        $questions = $this->authService->startAuthenticationAttempt($caseData);

        $formattedQuestions = [];
        $mapNumber = [
            '0' => 'one',
            '1' => 'two',
            '2' => 'three',
            '3' => 'four'
        ];
        //could also count how many questions have already been fetched prior
        $currentQuestionCount =
            isset($caseData->kbvQuestions) ? count(json_decode($caseData->kbvQuestions, true)) : null;
        //how do we know if we have already had questions created for this case?
        $counter = is_int($currentQuestionCount) ? $currentQuestionCount - 1 : 0;
        foreach ($questions as $question) {
            $number = $mapNumber[$counter];
            $formattedQuestions[$number] = [
                'number' => $number,
                'experianId' => $question->QuestionID,
                'question' => $question->Text,
                'prompts' => [
                    $question->AnswerFormat->AnswerList[0],
                    $question->AnswerFormat->AnswerList[1],
                    $question->AnswerFormat->AnswerList[2],
                    $question->AnswerFormat->AnswerList[3],
                    $question->AnswerFormat->AnswerList[4],
                ]

            ];
            $counter++;
        }
        //@todo array merge of questions upstream where it's saved back
        $this->logger->info(sprintf('Found %d questions', count($questions)));

        return ['formattedQuestions' => $formattedQuestions, 'questionsWithoutAnswers' => $formattedQuestions];
    }
}

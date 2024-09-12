<?php

declare(strict_types=1);

namespace Application\Experian\IIQ;

use Application\Fixtures\DataQueryHandler;
use Application\Fixtures\DataWriteHandler;
use Application\KBV\KBVServiceInterface;
use Application\Model\Entity\CaseData;
use Psr\Log\LoggerInterface;

class KBVService implements KBVServiceInterface
{
    public function __construct(
        private readonly IIQService $authService,
        private readonly LoggerInterface $logger,
        private readonly DataQueryHandler $queryHandler,
        private readonly DataWriteHandler $writeHandler
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

        $currentQuestionCount =
            isset($caseData->kbvQuestions) ? count(json_decode($caseData->kbvQuestions, true)) : null;

        $counter = is_int($currentQuestionCount) ? $currentQuestionCount - 1 : 0;
        foreach ($questions['questions'] as $question) {
            $number = $mapNumber[$counter];
            $formattedQuestions[$number] = [
                'number' => $number,
                'experianId' => $question->QuestionID,
                'question' => $question->Text,
                'prompts' => $question->AnswerFormat->AnswerList,
            ];
            $counter++;
        }

        //@todo array merge of questions upstream where it's saved back
        $this->logger->info(sprintf('Found %d questions', count($questions)));

        $this->saveIIQControlForRTQ($caseData->id, $questions['control']);

        return ['formattedQuestions' => $formattedQuestions, 'questionsWithoutAnswers' => $formattedQuestions];
    }

    public function checkAnswers(array $answers, string $uuid): bool
    {
        $caseData = $this->queryHandler->getCaseByUUID($uuid);

        //append experianId back to answers array
        $questions = json_decode($caseData->kbvQuestions, true);
        $iqqFormattedAnswers = [];
        foreach ($questions as $key => $question) {
            if (key_exists($key, $answers['answers'])) {
                $iqqFormattedAnswers = [
                    'experianId' => $question['experianId'],
                    'answer' => $answers['answers'][$key],
                    'flag' => 0
                ];
            }
        }

        $results = $this->authService->checkAnswers($iqqFormattedAnswers, $caseData);
    }

    private function saveIIQControlForRTQ(string $caseId, array $control): void
    {
        $this->writeHandler->updateCaseData(
            $caseId,
            'iqqControl',
            'S',
            json_encode($control)
        );
    }
}

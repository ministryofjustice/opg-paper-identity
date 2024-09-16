<?php

declare(strict_types=1);

namespace Application\Experian\IIQ;

use Application\Fixtures\DataQueryHandler;
use Application\Fixtures\DataWriteHandler;
use Application\KBV\KBVServiceInterface;

class KBVService implements KBVServiceInterface
{
    public function __construct(
        private readonly IIQService $authService,
        private readonly ConfigBuilder $configBuilder,
        private readonly DataQueryHandler $queryHandler,
        private readonly DataWriteHandler $writeHandler
    ) {
    }

    /**
     * @throws Exception\CannotGetQuestionsException
     * @psalm-suppress PossiblyNullArgument
     */
    public function fetchFormattedQuestions(string $uuid): array
    {
        $caseData = $this->queryHandler->getCaseByUUID($uuid);
        $saaRequest = $this->configBuilder->buildSAARequest($caseData);
        $questions = $this->authService->startAuthenticationAttempt($saaRequest);

        $formattedQuestions = [];
        $mapNumber = [
            '0' => 'one',
            '1' => 'two',
            '2' => 'three',
            '3' => 'four',
        ];

        foreach ($questions['questions'] as $counter => $question) {
            $number = $mapNumber[$counter];
            $formattedQuestions[] = [
                'number' => $number,
                'experianId' => $question->QuestionID,
                'question' => $question->Text,
                'prompts' => $question->AnswerFormat->AnswerList,
            ];
        }

        $this->saveIIQControlForRTQ($caseData->id, $questions['control']);

        return $formattedQuestions;
    }

    private function saveIIQControlForRTQ(string $caseId, array $control): void
    {
        $this->writeHandler->updateCaseData(
            $caseId,
            'iiqControl',
            'S',
            json_encode($control)
        );
    }
}
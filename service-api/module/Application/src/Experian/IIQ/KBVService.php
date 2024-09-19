<?php

declare(strict_types=1);

namespace Application\Experian\IIQ;

use Application\Fixtures\DataQueryHandler;
use Application\Fixtures\DataWriteHandler;
use Application\KBV\AnswersOutcome;
use Application\KBV\KBVServiceInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * @psalm-import-type Question from IIQService as IIQQuestion
 * @psalm-import-type Question from KBVServiceInterface as AppQuestion
 */
class KBVService implements KBVServiceInterface
{
    public function __construct(
        private readonly IIQService $iiqService,
        private readonly ConfigBuilder $configBuilder,
        private readonly DataQueryHandler $queryHandler,
        private readonly DataWriteHandler $writeHandler,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param IIQQuestion[] $iiqQuestions
     * @return AppQuestion[]
     */
    private function formatQuestions(array $iiqQuestions): array
    {
        $formattedQuestions = [];

        foreach ($iiqQuestions as $question) {
            $formattedQuestions[] = [
                'externalId' => $question->QuestionID,
                'question' => $question->Text,
                'prompts' => $question->AnswerFormat->AnswerList,
                'answered' => false,
            ];
        }

        return $formattedQuestions;
    }

    /**
     * @throws Exception\CannotGetQuestionsException
     * @return AppQuestion[]
     */
    public function fetchFormattedQuestions(string $uuid): array
    {
        $caseData = $this->queryHandler->getCaseByUUID($uuid);

        if (is_null($caseData)) {
            throw new RuntimeException('Case not found');
        }

        if (! is_null($caseData->kbvQuestions)) {
            return json_decode($caseData->kbvQuestions, true);
        }

        $saaRequest = $this->configBuilder->buildSAARequest($caseData);
        $questions = $this->iiqService->startAuthenticationAttempt($saaRequest);

        $formattedQuestions = $this->formatQuestions($questions['questions']);

        $this->writeHandler->updateCaseData(
            $caseData->id,
            'kbvQuestions',
            json_encode($formattedQuestions)
        );

        $this->writeHandler->updateCaseData(
            $caseData->id,
            'iiqControl',
            json_encode($questions['control'])
        );

        return $formattedQuestions;
    }

    /**
     * @param array<string, string> $answers
     */
    public function checkAnswers(array $answers, string $uuid): AnswersOutcome
    {
        $caseData = $this->queryHandler->getCaseByUUID($uuid);

        if (is_null($caseData)) {
            throw new RuntimeException('Case not found');
        }

        if (is_null($caseData->kbvQuestions)) {
            throw new RuntimeException('KBV questions have not been created yet');
        }

        /** @var AppQuestion[] $questions */
        $questions = json_decode($caseData->kbvQuestions, true);
        $iiqFormattedAnswers = [];
        foreach ($questions as &$question) {
            if (key_exists($question['externalId'], $answers)) {
                $iiqFormattedAnswers[] = [
                    'experianId' => $question['externalId'],
                    'answer' => $answers[$question['externalId']],
                    'flag' => '0',
                ];

                $question['answered'] = true;
            }
        }

        $rtqRequest = $this->configBuilder->buildRTQRequest($iiqFormattedAnswers, $caseData);
        $result = $this->iiqService->responseToQuestions($rtqRequest);

        $nextTransactionId = $result['result']['NextTransId']->string;

        if (isset($result['questions'])) {
            $questions = [
                ...$questions,
                ...$this->formatQuestions($result['questions']),
            ];
        }

        $this->writeHandler->updateCaseData(
            $caseData->id,
            'kbvQuestions',
            json_encode($questions)
        );

        if ($nextTransactionId === 'END') {
            if (
                isset($result['result']['AuthenticationResult']) &&
                $result['result']['AuthenticationResult'] === 'Authenticated'
            ) {
                return AnswersOutcome::CompletePass;
            } else {
                return AnswersOutcome::CompleteFail;
            }
        } elseif ($nextTransactionId === 'RTQ') {
            return AnswersOutcome::Incomplete;
        }

        $this->logger->error('Unknown IIQ transaction', [
            'transaction' => $nextTransactionId,
        ]);

        throw new RuntimeException('Cannot process KBV response');
    }
}

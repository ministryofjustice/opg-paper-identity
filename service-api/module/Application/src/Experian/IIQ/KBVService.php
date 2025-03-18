<?php

declare(strict_types=1);

namespace Application\Experian\IIQ;

use Application\Fixtures\DataQueryHandler;
use Application\Fixtures\DataWriteHandler;
use Application\KBV\AnswersOutcome;
use Application\KBV\KBVServiceInterface;
use Application\Model\Entity\IdentityIQ;
use Application\Model\Entity\IIQControl;
use Application\Model\Entity\KBVQuestion;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * @psalm-import-type Question from IIQService as IIQQuestion
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
     * @return KBVQuestion[]
     */
    private function formatQuestions(array $iiqQuestions): array
    {
        $formattedQuestions = [];

        foreach ($iiqQuestions as $question) {
            $formattedQuestions[] = KBVQuestion::fromArray([
                'externalId' => $question->QuestionID,
                'question' => $question->Text,
                'prompts' => $question->AnswerFormat->AnswerList,
                'answered' => false,
            ]);
        }

        return $formattedQuestions;
    }

    /**
     * @return KBVQuestion[]
     * @throws Exception\CannotGetQuestionsException
     */
    public function fetchFormattedQuestions(string $uuid): array
    {
        $caseData = $this->queryHandler->getCaseByUUID($uuid);

        if (is_null($caseData)) {
            throw new RuntimeException('Case not found');
        }

        if ($caseData->identityIQ && count($caseData->identityIQ->kbvQuestions)) {
            return $caseData->identityIQ->kbvQuestions;
        }

        $saaRequest = $this->configBuilder->buildSAARequest($caseData);
        $questions = $this->iiqService->startAuthenticationAttempt($saaRequest);

        $formattedQuestions = $this->formatQuestions($questions['questions']);

        $identityIQ = [
            'kbvQuestions' => $formattedQuestions,
            'iiqControl' => [
                'urn' => $questions['control']['URN'],
                'authRefNo' => $questions['control']['AuthRefNo'],
            ]
        ];

        if (empty($questions['questions'])) {
            $identityIQ['thinfile'] = true;
        }

        $this->writeHandler->updateCaseData(
            $caseData->id,
            'identityIQ',
            $identityIQ
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

        if (! $caseData->identityIQ || ! count($caseData->identityIQ->kbvQuestions)) {
            throw new RuntimeException('KBV questions have not been created yet');
        }

        $iiqFormattedAnswers = [];
        foreach ($caseData->identityIQ->kbvQuestions as &$question) {
            if (key_exists($question->externalId, $answers)) {
                $iiqFormattedAnswers[] = [
                    'experianId' => $question->externalId,
                    'answer' => $answers[$question->externalId],
                    'flag' => '0',
                ];

                $question->answered = true;
            }
        }

        $rtqRequest = $this->configBuilder->buildRTQRequest($iiqFormattedAnswers, $caseData);
        $result = $this->iiqService->responseToQuestions($rtqRequest);

        $nextTransactionId = $result['result']['NextTransId']->string;

        if (isset($result['questions'])) {
            $questions = [
                ...$caseData->identityIQ->kbvQuestions,
                ...$this->formatQuestions($result['questions']),
            ];
        } else {
            $questions = $caseData->identityIQ->kbvQuestions;
        }


        $this->writeHandler->updateCaseData(
            $caseData->id,
            'identityIQ.kbvQuestions',
            $questions
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

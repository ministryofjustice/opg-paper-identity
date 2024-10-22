<?php

declare(strict_types=1);

namespace Application\Experian\Crosscore\FraudApi\DTO;

use Application\Experian\Crosscore\FraudApi\FraudApiException;

class ResponseDTO
{
    public function __construct(
        private readonly array $response
    ) {
    }

    /**
     * @throws FraudApiException
     */
    public function toArray(): array
    {
        return [
            'decisionText' => $this->decisionText(),
            'decision' => $this->decision(),
            'score' => $this->score(),
        ];
    }

    /**
     * @throws FraudApiException
     */
    public function responseHeader(): array
    {
        try {
            return $this->response['responseHeader'];
        } catch (\Exception $exception) {
            throw new FraudApiException($exception->getMessage());
        }
    }

    /**
     * @throws FraudApiException
     */
    public function decision(): string
    {
        $decision = "";
        try {
            foreach ($this->response['clientResponsePayload']['orchestrationDecisions'] as $value) {
                if ($value['decisionSource'] == 'MachineLearning') {
                    $decision = $value['decision'];
                }
            }
        } catch (\Exception $exception) {
            throw new FraudApiException($exception->getMessage());
        }
        return $decision;
    }

    /**
     * @throws FraudApiException
     */
    public function decisionText(): string
    {
        $decisionText = "";

        try {
            foreach ($this->response['clientResponsePayload']['orchestrationDecisions'] as $value) {
                if ($value['decisionSource'] == 'MachineLearning') {
                    $decisionText = $value['decisionText'];
                }
            }
        } catch (\Exception $exception) {
            throw new FraudApiException($exception->getMessage());
        }
        return $decisionText;
    }

    public function score(): int
    {
        $score = 0;
        try {
            foreach ($this->response['clientResponsePayload']['orchestrationDecisions'] as $value) {
                if ($value['decisionSource'] == 'MachineLearning') {
                    $score = $value['score'];
                }
            }
        } catch (\Exception $exception) {
            throw new FraudApiException($exception->getMessage());
        }
        return $score;
    }
}

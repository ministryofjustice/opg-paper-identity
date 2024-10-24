<?php

declare(strict_types=1);

namespace Application\Experian\Crosscore\FraudApi\DTO;

use Application\Experian\Crosscore\FraudApi\FraudApiException;

class ResponseDTO
{
    private string $decision = "";

    private string $decisionText = "";

    private int $score = 0;

    public function __construct(
        private readonly array $response
    ) {
        try {
            foreach ($this->response['clientResponsePayload']['orchestrationDecisions'] as $value) {
                if ($value['decisionSource'] == 'MachineLearning') {
                    $this->decision = $value['decision'];
                    $this->decisionText = $value['decisionText'];
                    $this->score = $value['score'];
                }
            }
        } catch (\Exception $exception) {
            throw new FraudApiException($exception->getMessage());
        }
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

    public function decision(): string
    {
        return $this->decision;
    }

    public function decisionText(): string
    {
        return $this->decisionText;
    }

    public function score(): int
    {
        return $this->score;
    }
}

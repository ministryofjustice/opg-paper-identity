<?php

declare(strict_types=1);

namespace Application\Experian\Crosscore\FraudApi\DTO;

use Application\Experian\Crosscore\FraudApi\FraudApiException;

class ResponseDTO
{
    private string $decision = "";

    private int $score = 0;

    public function __construct(
        private readonly array $response
    ) {
        $set = false;
        try {
            foreach ($this->response['clientResponsePayload']['orchestrationDecisions'] as $value) {
                if ($value['decisionSource'] == 'MachineLearning') {
                    $this->decision = $value['decision'];
                    $this->score = $value['score'];
                    $set = true;
                }
            }
            if (! $set) {
                throw new FraudApiException("Machine learning data not present.");
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
            'decision' => $this->decision(),
            'score' => $this->score(),
        ];
    }

    public function decision(): string
    {
        return $this->decision;
    }

    public function score(): int
    {
        return $this->score;
    }
}

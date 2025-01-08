<?php

declare(strict_types=1);

namespace Application\Experian\Crosscore\FraudApi\DTO;

use Application\Experian\Crosscore\FraudApi\FraudApiException;

class ResponseDTO
{
    private string $decision = "";

    private int $score = 0;

    public function __construct(
        array $response
    ) {
        try {
            $found = false;
            foreach ($response['clientResponsePayload']['orchestrationDecisions'] as $value) {
                if ($value['decisionSource'] == 'MachineLearning') {
                    $found = true;
                    $this->score = $value['score'];
                }
            }
            if (! $found) {
                throw new FraudApiException('Fraudscore response does not contain required score data');
            }
            $this->decision = $response['responseHeader']['overallResponse']['decision'];
        } catch (\Exception $exception) {
            throw new FraudApiException(
                "Fraudscore response does not contain required decision data: " .
                $exception->getMessage()
            );
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

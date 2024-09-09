<?php

declare(strict_types=1);

namespace Application\Experian\IIQ;

use Application\Experian\IIQ\Exception\CannotGetQuestionsException;
use Application\Experian\IIQ\Soap\IIQClient;
use Application\Model\Entity\CaseData;
use Psr\Log\LoggerInterface;

class IIQService
{
    public function __construct(
        private readonly IIQClient $client,
        private readonly ConfigBuilder $builder,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @throws CannotGetQuestionsException
     */
    public function startAuthenticationAttempt(CaseData $caseData): array
    {
        $request = $this->client->SAA([$this->builder->buildSAA($caseData)]);

        if ($request->SAAResult->Results->Outcome !== 'Authentication Questions returned') {
            $this->logger->error($request->SAAResult->Results->Outcome);
            throw new CannotGetQuestionsException("Error retrieving questions");
        }
        if ($request->SAAResult->Results->NextTransId->string !== 'RTQ') {
            $this->logger->error($request->SAAResult->Results->NextTransId->string);
            throw new CannotGetQuestionsException("Error retrieving questions");
        }

        return (array)$request->SAAResult->Questions->Question;
    }
}

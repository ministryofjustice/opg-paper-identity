<?php

declare(strict_types=1);

namespace Application\Experian\IIQ;

use Application\Experian\IIQ\Exception\CannotGetQuestionsException;
use Application\Experian\IIQ\Soap\IIQClient;
use Application\Model\Entity\CaseData;
use Psr\Log\LoggerInterface;
use SoapFault;

class IIQService
{
    private bool $isAuthenticated = false;
    public function __construct(
        private readonly AuthManager $authManager,
        private readonly IIQClient $client,
        private readonly ConfigBuilder $builder,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    private function withAuthentication(callable $callback): mixed
    {
        if (! $this->isAuthenticated) {
            $this->client->__setSoapHeaders([
                $this->authManager->buildSecurityHeader(),
            ]);

            $this->isAuthenticated = true;
        }

        try {
            return $callback();
        } catch (SoapFault $e) {
            if ($e->getMessage() === 'Unauthorized') {
                $this->logger->info('IIQ API replied unauthorised, retrying with new token');

                $this->client->__setSoapHeaders([
                    $this->authManager->buildSecurityHeader(true),
                ]);

                return $callback();
            } else {
                throw $e;
            }
        }
    }

    /**
     * @throws CannotGetQuestionsException
     * @throws SoapFault
     */
    public function startAuthenticationAttempt(CaseData $caseData): array
    {
        return $this->withAuthentication(function () use ($caseData) {
            $request = $this->client->SAA([
                'sAARequest' => [
                    $this->builder->buildSAA($caseData)
                ]
            ]);

            if ($request->SAAResult->Results) {
                if ($request->SAAResult->Results->Outcome !== 'Authentication Questions returned') {
                    $this->logger->error($request->SAAResult->Results->Outcome);
                    throw new CannotGetQuestionsException("Error retrieving questions");
                }
                if ($request->SAAResult->Results->NextTransId->string !== 'RTQ') {
                    $this->logger->error($request->SAAResult->Results->NextTransId->string);
                    throw new CannotGetQuestionsException("Error retrieving questions");
                }
            }

            //@todo remove this log after debugging
            $this->logger->info('sAAResponse', $request);

            //need to pass these control structure for RTQ transaction
            $control = [];
            $control['URN'] = $request->SAAResult->Control->URN;
            $control['AuthRefNo'] = $request->SAAResult->Control->AuthRefNo;

            return ['questions' => (array)$request->SAAResult->Questions->Question, 'control' => $control];
        });
    }

    /**
     * @throws SoapFault
     * @psalm-suppress PossiblyUnusedReturnValue
     */
    public function checkAnswers(array $answers, CaseData $caseData): array
    {
        return $this->withAuthentication(function () use ($answers, $caseData) {
            $request = $this->client->RTQ([
                'web:rTQRequest' => [
                    $this->builder->buildRTQ($answers, $caseData)
                ]
            ]);
            //@todo determine what to return here
            return ['result' => $request];
        });
    }
}

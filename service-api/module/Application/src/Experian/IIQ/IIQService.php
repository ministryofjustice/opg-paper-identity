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
     */
    public function startAuthenticationAttempt(CaseData $caseData): array
    {
        return $this->withAuthentication(function () use ($caseData) {
            $request = $this->client->SAA([
                'sAARequest' => [
                    $this->builder->buildSAA($caseData)
                ]
            ]);

            //@todo remove this after debugging
            $this->logger->info($request->SAAResponse->SAAResult->Control->URN);
            $this->logger->info($request->SAAResult->Control->URN);


            if ($request->SAAResponse->SAAResult) {
                if ($request->SAAResponse->SAAResult->Results->Outcome !== 'Authentication Questions returned') {
                    $this->logger->error($request->SAAResponse->SAAResult->Results->Outcome);
                    throw new CannotGetQuestionsException("Error retrieving questions");
                }
                if ($request->SAAResponse->SAAResult->Results->NextTransId->string !== 'RTQ') {
                    $this->logger->error($request->SAAResponse->SAAResult->Results->NextTransId->string);
                    throw new CannotGetQuestionsException("Error retrieving questions");
                }
            } else {
                throw new CannotGetQuestionsException("No results");
            }

            return (array)$request->SAAResponse->SAAResult->Questions->Question;
        });
    }
}

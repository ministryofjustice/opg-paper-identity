<?php

declare(strict_types=1);

namespace Application\Experian\IIQ;

use Application\Experian\IIQ\Exception\CannotGetQuestionsException;
use Application\Experian\IIQ\Soap\IIQClient;
use Psr\Log\LoggerInterface;
use SoapFault;

/**
 * @psalm-type SAARequest = array{
 *   Applicant: array{
 *     ApplicantIdentifier: string,
 *     Name: array{
 *       Title: string,
 *       Forename: string,
 *       Surname: string,
 *     },
 *     DateOfBirth: array{
 *       CCYY: string,
 *       MM: string,
 *       DD: string,
 *     }
 *   },
 *   ApplicationData: array{
 *     SearchConsent: "Y",
 *   },
 *   LocationDetails: array{
 *     LocationIdentifier: string,
 *     UKLocation: array{
 *       HouseName: string,
 *       Street: string,
 *       District: string,
 *       PostTown: string,
 *       Postcode: string,
 *     },
 *   }
 * }
 *
 * @psalm-type Question = object{
 *   QuestionID: string,
 *   Text: string,
 *   Tooltip: string,
 *   AnswerFormat: object{
 *     Identifier: string,
 *     FieldType: "G",
 *     AnswerList: string[]
 *   }
 * }
 */
class IIQService
{
    private bool $isAuthenticated = false;
    public function __construct(
        private readonly AuthManager $authManager,
        private readonly IIQClient $client,
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
     * @psalm-suppress MixedReturnTypeCoercion
     * @psalm-param SAARequest $saaRequest
     * @return array{
     *   questions: Question[],
     *   control: array{
     *     URN: string,
     *     AuthRefNo: string,
     *   }
     * }
     */
    public function startAuthenticationAttempt(array $saaRequest): array
    {
        return $this->withAuthentication(function () use ($saaRequest) {
            $request = $this->client->SAA([
                'sAARequest' => $saaRequest,
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

            //need to pass these control structure for RTQ transaction
            $control = [];
            $control['URN'] = $request->SAAResult->Control->URN;
            $control['AuthRefNo'] = $request->SAAResult->Control->AuthRefNo;

            return ['questions' => (array)$request->SAAResult->Questions->Question, 'control' => $control];
        });
    }
}

<?php

declare(strict_types=1);

namespace Application\Experian\IIQ;

use Application\Experian\IIQ\Exception\CannotGetQuestionsException;
use Application\Experian\IIQ\Soap\IIQClient;
use Exception;
use Psr\Log\LoggerInterface;
use SoapFault;

/**
 * @psalm-type SAARequest = array{
 *   Applicant: array{
 *     ApplicantIdentifier: string,
 *     Name: array{
 *       Title: string,
 *       Forename: string|null,
 *       Surname: string|null,
 *     },
 *     DateOfBirth: array{
 *       CCYY: string,
 *       MM: string,
 *       DD: string,
 *     }
 *   },
 *   ApplicationData: array{
 *     SearchConsent: "Y",
 *     Product?: string
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
 *
 * @psalm-type Control = array{
 *   URN: string,
 *   AuthRefNo: string,
 * }
 *
 * @psalm-type RTQRequest = array{
 *   Control: Control,
 *   Responses: array{
 *     Response: array{
 *       QuestionID: string,
 *       AnswerGiven: string,
 *       CustResponseFlag: int,
 *       AnswerActionFlag: string,
 *     }[]
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
     *   control: Control
     * }
     */
    public function startAuthenticationAttempt(array $saaRequest): array
    {
        return $this->withAuthentication(function () use ($saaRequest) {
            $request = $this->client->SAA([
                'sAARequest' => $saaRequest,
            ]);

            $control = [
                'URN' => $request->SAAResult->Control->URN,
                'AuthRefNo' => $request->SAAResult->Control->AuthRefNo,
            ];

            if (
                $request->SAAResult->Results->Outcome === 'Insufficient Questions (Unable to Authenticate)' &&
                $request->SAAResult->Results->NextTransId->string === 'END'
            ) {
                return ['questions' => [], 'control' => $control];
            }

            if (
                $request->SAAResult->Results->Outcome === 'Authentication Questions returned' &&
                $request->SAAResult->Results->NextTransId->string === 'RTQ'
            ) {
                return ['questions' => (array)$request->SAAResult->Questions->Question, 'control' => $control];
            }

            $this->logger->error('Error retrieving questions', [
                'outcome' => $request->SAAResult->Results->Outcome,
                'nextTransId' => $request->SAAResult->Results->NextTransId->string,
            ]);

            throw new CannotGetQuestionsException("Error retrieving questions");
        });
    }

    /**
     * @throws SoapFault
     * @psalm-suppress MixedReturnTypeCoercion
     * @psalm-param RTQRequest $rtqRequest
     * @return array{
     *   questions?: Question[],
     *   result: array{
     *     AuthenticationResult?: "Not Authenticated"|"Authenticated",
     *     NextTransId: object{string: "RTQ"|"END"|string}
     *   }
     * }
     */
    public function responseToQuestions(array $rtqRequest): array
    {
        return $this->withAuthentication(function () use ($rtqRequest) {
            $request = $this->client->RTQ([
                'rTQRequest' => $rtqRequest,
            ]);

            if (isset($request->RTQResult->Error)) {
                throw new Exception($request->RTQResult->Error->Message);
            }

            $ret = ['result' => (array)$request->RTQResult->Results];

            if (isset($request->RTQResult?->Questions?->Question)) {
                $question = $request->RTQResult->Questions->Question;

                $ret['questions'] = is_array($question) ? $question : [$question];
            }

            return $ret;
        });
    }
}

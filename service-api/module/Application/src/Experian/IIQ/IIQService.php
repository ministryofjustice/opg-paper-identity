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

                $securityHeader = $this->authManager->buildSecurityHeader(true);

                $this->client->__setSoapHeaders([
                    $securityHeader
                ]);

                $this->logger->info(
                    'SOAP_AUTH: ' . json_encode($securityHeader)
                );

                return $callback();
            } else {
                $this->logger->info(
                    'SOAP_ERROR: ' . $e->getMessage()
                );
                throw $e;
            }
        }
    }

    /**
     * @return array{
     *   questions: Question[],
     *   control: Control
     * }
     * @throws SoapFault
     * @psalm-suppress MixedReturnTypeCoercion
     * @psalm-param SAARequest $saaRequest
     * @throws CannotGetQuestionsException
     */
    public function startAuthenticationAttempt(array $saaRequest): array
    {
        $this->logger->info(
            'SAA_REQUEST: ' . json_encode($saaRequest)
        );

        $questions = $this->withAuthentication(function () use ($saaRequest) {

            try {
                $request = $this->client->SAA([
                    'sAARequest' => $saaRequest,
                ]);
                $this->logger->info(
                    'SAA_OUTCOME: ' . $request->SAAResult->Results->Outcome
                );

                if ($request->SAAResult->Results) {
                    if (
                        $request->SAAResult->Results->Outcome !== 'Authentication Questions returned' &&
                        $request->SAAResult->Results->Outcome !== 'Insufficient Questions (Unable to Authenticate)'
                    ) {
                        $this->logger->error($request->SAAResult->Results->Outcome);
                        $this->logger->info(
                            'SAA_ERROR: ' . $request->SAAResult->Results->Outcome
                        );
                        throw new CannotGetQuestionsException("Error retrieving questions");
                    }
                    if ($request->SAAResult->Results->NextTransId->string !== 'RTQ') {
                        $this->logger->error($request->SAAResult->Results->NextTransId->string);
                        $this->logger->info(
                            'SAA_ERROR: ' . $request->SAAResult->Results->NextTransId->string
                        );
                        throw new CannotGetQuestionsException("Error retrieving questions");
                    }
                }

                //need to pass these control structure for RTQ transaction
                $control = [];
                $control['URN'] = $request->SAAResult->Control->URN;
                $control['AuthRefNo'] = $request->SAAResult->Control->AuthRefNo;

                return ['questions' => (array)$request->SAAResult->Questions->Question, 'control' => $control];
            } catch (\Exception $exception) {
                $this->logger->info(
                    'SAA_EXCEPTOIN: ' . $exception->getMessage()
                );
            }
        });

        $this->logger->info(
            'SAA_QUESTIONS: ' . json_encode($questions)
        );

        return $questions;
    }

    /**
     * @return array{
     *   questions?: Question[],
     *   result: array{
     *     AuthenticationResult?: "Not Authenticated"|"Authenticated",
     *     NextTransId: object{string: "RTQ"|"END"|string}
     *   }
     * }
     * @throws SoapFault
     * @psalm-suppress MixedReturnTypeCoercion
     * @psalm-param RTQRequest $rtqRequest
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

            if (isset($request?->RTQResult?->Questions?->Question)) {
                $question = $request->RTQResult->Questions->Question;

                $ret['questions'] = is_array($question) ? $question : [$question];
            }

            return $ret;
        });
    }
}

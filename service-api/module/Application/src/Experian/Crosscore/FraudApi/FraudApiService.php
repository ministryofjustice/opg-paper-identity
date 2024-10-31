<?php

declare(strict_types=1);

namespace Application\Experian\Crosscore\FraudApi;

use Application\Experian\Crosscore\AuthApi\AuthApiException;
use Application\Experian\Crosscore\AuthApi\AuthApiService;
use Application\Experian\Crosscore\FraudApi\DTO\RequestDTO;
use Application\Experian\Crosscore\FraudApi\DTO\ResponseDTO;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Laminas\Http\Response;
use Monolog\Logger;
use Ramsey\Uuid\Uuid;
use Telemetry\Instrumentation\Laminas;

class FraudApiService
{
    public function __construct(
        private readonly Client $client,
        private readonly AuthApiService $experianCrosscoreAuthApiService,
        private readonly array $config
    ) {
    }

    public int $authCount = 0;

    /**
     * @throws GuzzleException
     * @throws AuthApiException
     */
    public function makeHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Authorization' => $this->experianCrosscoreAuthApiService->retrieveCachedTokenResponse(),
            'X-User-Domain' => $this->config['domain']
        ];
    }

    /**
     * @throws GuzzleException
     * @throws FraudApiException
     */
    public function getFraudScore(
        RequestDTO $experianCrosscoreFraudRequestDTO
    ): ResponseDTO {
        return $this->makeRequest($experianCrosscoreFraudRequestDTO);
    }

    /**
     * @throws GuzzleException
     * @throws FraudApiException
     * @psalm-suppress InvalidReturnType
     */
    public function makeRequest(
        RequestDTO $experianCrosscoreFraudRequestDTO
    ): ResponseDTO {
        $this->authCount++;
        try {
            $postBody = $this->constructRequestBody($experianCrosscoreFraudRequestDTO);

            $response = $this->client->request(
                'POST',
                '/3',
                [
                    'headers' => $this->makeHeaders(),
                    'json' => json_encode($postBody)
                ]
            );

            $responseArray = json_decode($response->getBody()->getContents(), true);

            return new ResponseDTO(
                $responseArray
            );
        } catch (ClientException $clientException) {
            if ($clientException->getResponse()->getStatusCode() == Response::STATUS_CODE_401 && $this->authCount < 2) {
                $this->experianCrosscoreAuthApiService->authenticate();
                $this->makeRequest($experianCrosscoreFraudRequestDTO);
            } else {
                throw $clientException;
            }
        } catch (\Exception $exception) {
            throw new FraudApiException($exception->getMessage());
        }
    }

    public function constructRequestBody(
        RequestDTO $experianCrosscoreFraudRequestDTO
    ): array {
        $requestUuid = Uuid::uuid4()->toString();
        $personId = $this->makePersonId($experianCrosscoreFraudRequestDTO);
        $addressDTO = $experianCrosscoreFraudRequestDTO->address();


        return [
            "header" => [
                "tenantId" => $this->config['tenantId'],
                "requestType" => "FraudScore",
                "clientReferenceId" => "$requestUuid-FraudScore-continue",
                "expRequestId" => $requestUuid,
                "messageTime" => date("Y-m-d\TH:i:s.000\Z"),
                "options" => []
            ],
            "payload" => [
                "contacts" => [
                    [
                        "id" => $personId,
                        "person" => [
                            "personDetails" => [
                                "dateOfBirth" => $experianCrosscoreFraudRequestDTO->dob()
                            ],
                            "personIdentifier" => "",
                            "names" => [
                                [
                                    "type" => "CURRENT",
                                    "firstName" => $experianCrosscoreFraudRequestDTO->firstName(),
                                    "surName" => $experianCrosscoreFraudRequestDTO->lastName(),
                                    "id" => "NAME1"
                                ]
                            ],
                            "addresses" => [
                                [
                                    "id" => "MACADDRESS1",
                                    "addressType" => "CURRENT",
                                    "indicator" => "RESIDENTIAL",
                                    "buildingName" => $addressDTO->line1(),
                                    "street" => $addressDTO->line2(),
                                    "street2" => $addressDTO->line3(),
                                    "postal" => $addressDTO->postcode(),
                                    "postTown" => $addressDTO->town(),
                                    "county" => $addressDTO->country()
                                ]
                            ]
                        ],
                    ]
                ],
                "control" => [
                    [
                        "option" => "ML_MODEL_CODE",
                        "value" => "bfs"
                    ]
                ],
                "application" => [
                    "applicants" => [
                        [
                            "id" => "MA_APPLICANT1",
                            "contactId" => $personId,
                            "type" => "INDIVIDUAL",
                            "applicantType" => "MAIN_APPLICANT",
                            "consent" => "true"
                        ]
                    ]
                ],
                "source" => "WEB"
            ]
        ];
    }

    private function makePersonId(
        RequestDTO $experianCrosscoreFraudRequestDTO
    ): string {

        $fInitial = strtoupper(substr($experianCrosscoreFraudRequestDTO->firstName(), 0, 1));
        $lInitial = strtoupper(substr($experianCrosscoreFraudRequestDTO->lastName(), 0, 1));

        return sprintf(
            '%s%s1',
            $fInitial,
            $lInitial
        );
    }
}

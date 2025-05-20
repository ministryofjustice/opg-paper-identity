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
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Telemetry\Instrumentation\Laminas;

class FraudApiService
{
    public function __construct(
        private readonly Client $client,
        private readonly AuthApiService $experianCrosscoreAuthApiService,
        private readonly LoggerInterface $logger,
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
            'Authorization' => sprintf(
                'Bearer %s',
                $this->experianCrosscoreAuthApiService->retrieveCachedTokenResponse()
            ),
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
                '3',
                [
                    'headers' => $this->makeHeaders(),
                    'json' => $postBody
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
                $response = $clientException->getResponse();
                $responseBodyAsString = $response->getBody()->getContents();
                $this->logger->error('GuzzleFraudApiException: ' . $responseBodyAsString);
                throw $clientException;
            }
        } catch (\Exception $exception) {
            $this->logger->error('FraudApiException: ' . $exception->getMessage(), ['exception' => $exception]);
            throw new FraudApiException($exception->getMessage());
        }
    }

    public function constructRequestBody(
        RequestDTO $experianCrosscoreFraudRequestDTO
    ): array {
        $requestUuid = Uuid::uuid4()->toString();
        $personId = $this->makePersonId($experianCrosscoreFraudRequestDTO);
        $nameId = $this->makePersonId($experianCrosscoreFraudRequestDTO, true);
        $addressDTO = $experianCrosscoreFraudRequestDTO->address();

        return [
            "header" => [
                "tenantId" => $this->config['tenantId'],
                "requestType" => "FraudScore",
                "clientReferenceId" => "$requestUuid-FraudScore-continue",
                "expRequestId" => null,
                "messageTime" => date("Y-m-d\TH:i:s\Z"),
                "options" => new \stdClass()
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
                                    "id" => $nameId
                                ]
                            ]
                        ],
                        "addresses" => [
                            [
                                "id" => "MACADDRESS1",
                                "addressType" => "CURRENT",
                                "indicator" => "RESIDENTIAL",
                                "buildingName" => $addressDTO->line1(),
                                "postal" => $addressDTO->postcode(),
                                "county" => ""
                            ]
                        ]
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
        RequestDTO $experianCrosscoreFraudRequestDTO,
        bool $name = false
    ): string {
        $lInitial = strtoupper(substr($experianCrosscoreFraudRequestDTO->lastName(), 0, 2));

        if ($name) {
            return sprintf(
                '%sNAME1',
                $lInitial
            );
        }

        return sprintf(
            '%s1',
            $lInitial
        );
    }
}

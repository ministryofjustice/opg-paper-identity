<?php

declare(strict_types=1);

namespace Application\Services\Experian\FraudApi;

use Application\Cache\ApcHelper;
use Application\Services\Experian\AuthApi\DTO\ExperianCrosscoreAuthRequestDTO;
use Application\Services\Experian\AuthApi\DTO\ExperianCrosscoreAuthResponseDTO;
use Application\Services\Experian\AuthApi\ExperianCrosscoreAuthApiException;
use Application\Services\Experian\FraudApi\DTO\CrosscoreAddressDTO;
use Application\Services\Experian\FraudApi\DTO\ExperianCrosscoreFraudRequestDTO;
use Application\Services\Experian\FraudApi\DTO\ExperianCrosscoreFraudResponseDTO;
use Application\Services\Experian\AuthApi\ExperianCrosscoreAuthApiService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Ramsey\Uuid\Uuid;

class ExperianCrosscoreFraudApiService
{
    public function __construct(
        private readonly Client $client,
        private readonly ExperianCrosscoreAuthApiService $experianCrosscoreAuthApiService,
        private readonly array $config
    ) {
    }

    /**
     * @throws GuzzleException
     * @throws ExperianCrosscoreAuthApiException
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
     * @throws ExperianCrosscoreFraudApiException
     */
    public function getFraudScore(
        ExperianCrosscoreFraudRequestDTO $experianCrosscoreFraudRequestDTO
    ): array {
        $response = $this->makeRequest($experianCrosscoreFraudRequestDTO);
        return $response->toArray();
    }

    /**
     * @throws GuzzleException
     * @throws ExperianCrosscoreFraudApiException
     */
    public function makeRequest(
        ExperianCrosscoreFraudRequestDTO $experianCrosscoreFraudRequestDTO
    ): ExperianCrosscoreFraudResponseDTO {
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

            return new ExperianCrosscoreFraudResponseDTO(
                $responseArray
            );
        } catch (\Exception $exception) {
            throw new ExperianCrosscoreFraudApiException($exception->getMessage());
        }
    }

    public function constructRequestBody(
        ExperianCrosscoreFraudRequestDTO $experianCrosscoreFraudRequestDTO
    ): array {
        $requestUuid = Uuid::uuid4()->toString();
        $personId = $this->makePersonId($experianCrosscoreFraudRequestDTO);
        $addressDTO = $experianCrosscoreFraudRequestDTO->address();

        $body = [
            'header' => [
                "tenantId" => $this->config['tenantId'],
                "requestType" => "FraudScore",
                "clientReferenceId" => "$requestUuid-FraudScore-continue",
                "expRequestId" => $requestUuid,
                "messageTime" => date("Y-m-d\TH:i:s.000\Z", time()),
                "options" => []
            ],
            'payload' => [
                'contacts' => [
                    [
                        "id" => $personId,
                        "person" => [
                            "personDetails" => [
                                "dateOfBirth" => $experianCrosscoreFraudRequestDTO->dob()
                            ],
                            "names" => [
                                "type" => "CURRENT",
                                "firstName" => $experianCrosscoreFraudRequestDTO->firstName(),
                                "surName" => $experianCrosscoreFraudRequestDTO->lastName(),
                                "id" => "MANAME1"
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
                        ]
                    ]
                ],
                'control' => [
                    [
                        "option" => "ML_MODEL_CODE",
                        "value" => "bfs"
                    ]
                ],
                'application' => [
                    "applicants" => [
                        [
                            "id" => "MA_APPLICANT1",
                            "contactId" => $personId,
                            "type" => "INDIVIDUAL",
                            "applicantType" => "MAIN_APPLICANT",
                            "consent" => "true"
                        ]
                    ]
                ]
            ],
            'source' => 'WEB'
        ];

        return $body;
    }

    private function makePersonId(
        ExperianCrosscoreFraudRequestDTO $experianCrosscoreFraudRequestDTO
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

<?php

declare(strict_types=1);

namespace Application\Services\Experian\FraudApi;

use Application\Cache\ApcHelper;
use Application\Services\Experian\AuthApi\ExperianCrosscoreAuthApiService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class ExperianCrosscoreFraudApiService
{
    public function __construct(
        private readonly Client $client,
        private readonly ExperianCrosscoreAuthApiService $experianCrosscoreAuthApiService
    ) {
    }

    public function makeHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'X-User-Domain' => getenv('EXPERIAN_DOMAIN')
        ];
    }

    /**
     * @throws GuzzleException
     * @throws ExperianCrosscoreFraudApiException
     */
    public function getFraudScore(): array
    {
        $tokenResponse = $this->getToken($credentials);

        $this->cacheTokenResponse($tokenResponse);

        return $tokenResponse;
    }

    /**
     * @throws GuzzleException
     * @throws ExperianCrosscoreFraudApiException
     */
    private function getToken(
        ExperianCrosscoreFraudRequestDTO $experianCrosscoreAuthRequestDTO
    ): ExperianCrosscoreFraudResponseDTO {
        try {
            $response = $this->client->request(
                'POST',
                'oauth2/experianone/v1/token',
                [
                    'headers' => $this->makeHeaders(),
                    'json' => $experianCrosscoreAuthRequestDTO->toArray()
                ]
            );

            $responseArray = json_decode($response->getBody()->getContents(), true);

            return new ExperianCrosscoreFraudResponseDTO(
                $responseArray['access_token'],
                $responseArray['refresh_token'],
                $responseArray['issued_at'],
                $responseArray['expires_in'],
                $responseArray['token_type']
            );
        } catch (\Exception $exception) {
            throw new ExperianCrosscoreFraudApiException($exception->getMessage());
        }
    }
}

<?php

declare(strict_types=1);

namespace Application\Services\Experian\FraudApi;

use Application\Cache\ApcHelper;
use Application\Services\Experian\AuthApi\ExperianCrosscoreAuthApiService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class ExperianCrosscoreFraudApiService
{
    const EXPIRY = 1800; // 30 minutes

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
    public function getFraudscore(): array
    {
        $credentials = $this->getCredentials();

        $tokenResponse = $this->getToken($credentials);

        $this->cacheTokenResponse($tokenResponse);

        return $tokenResponse;
    }


    /**
     * @throws GuzzleException
     * @throws ExperianCrosscoreFraudApiException
     */
    public function retrieveCachedToken(): string
    {
        $tokenResponse = json_decode(
            $this->apcHelper->getValue('experian_crosscore_access_token'),
            true
        );

        if (($tokenResponse['time'] + 1800) > time()) {
            return $tokenResponse['access_token'];
        } else {
            return $this->authenticate()->accessToken();
        }
    }

    /**
     * @throws ExperianCrosscoreFraudApiException
     */
    public function getCredentials(): ExperianCrosscoreFraudRequestDTO
    {
        try {
            return $this->experianCrosscoreAuthRequestDTO;
        } catch (\Exception $exception) {
            throw new ExperianCrosscoreFraudApiException($exception->getMessage());
        }
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
                'oauth2/experianone/v1/token', [
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

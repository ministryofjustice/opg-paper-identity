<?php

declare(strict_types=1);

namespace Application\Services\Experian\AuthApi;

use Application\Aws\Secrets\AwsSecret;
use Application\Cache\ApcHelper;
use Application\Services\Experian\AuthApi\DTO\ExperianCrosscoreAuthRequestDTO;
use Application\Services\Experian\AuthApi\DTO\ExperianCrosscoreAuthResponseDTO;
use Application\Services\Experian\AuthApi\ExperianCrosscoreAuthApiException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Ramsey\Uuid\Uuid;

class ExperianCrosscoreAuthApiService
{
    private const EXPIRY = 1800; // 30 minutes

    public function __construct(
        private readonly Client $client,
        private readonly ApcHelper $apcHelper,
        private readonly ExperianCrosscoreAuthRequestDTO $experianCrosscoreAuthRequestDTO
    ) {
    }

    private function generateXCorrelationId(): string
    {
        return Uuid::uuid4()->toString();
    }

    public function makeHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'X-Correlation-Id' => $this->generateXCorrelationId(),
            'X-User-Domain' => getenv('EXPERIAN_DOMAIN')
        ];
    }

    /**
     * @throws GuzzleException
     * @throws ExperianCrosscoreAuthApiException
     */
    public function authenticate(): ExperianCrosscoreAuthResponseDTO
    {
        $credentials = $this->getCredentials();

        $tokenResponse = $this->getToken($credentials);

        $this->cacheTokenResponse($tokenResponse);

        return $tokenResponse;
    }

    private function cacheTokenResponse(
        ExperianCrosscoreAuthResponseDTO $experianCrosscoreAuthResponseDTO,
    ): void {
        $this->apcHelper->setValue(
            'experian_crosscore_access_token',
            json_encode([
                'access_token' => $experianCrosscoreAuthResponseDTO->accessToken(),
                'time' => $experianCrosscoreAuthResponseDTO->issuedAt()
            ])
        );
    }

    /**
     * @throws GuzzleException
     * @throws ExperianCrosscoreAuthApiException
     */
    public function retrieveCachedTokenResponse(): string
    {
        $cachedToken = $this->apcHelper->getValue('experian_crosscore_access_token');

        if (! $cachedToken) {
            return $this->authenticate()->accessToken();
        }

        $tokenResponse = json_decode($cachedToken, true);

        if (is_null($tokenResponse) || ($tokenResponse['time'] + 1790) > time()) {
            return $tokenResponse['access_token'];
        } else {
            return $this->authenticate()->accessToken();
        }
    }

    /**
     * @throws ExperianCrosscoreAuthApiException
     */
    public function getCredentials(): ExperianCrosscoreAuthRequestDTO
    {
        try {
            return $this->experianCrosscoreAuthRequestDTO;
        } catch (\Exception $exception) {
            throw new ExperianCrosscoreAuthApiException($exception->getMessage());
        }
    }

    /**
     * @throws GuzzleException
     * @throws ExperianCrosscoreAuthApiException
     */
    private function getToken(
        ExperianCrosscoreAuthRequestDTO $experianCrosscoreAuthRequestDTO
    ): ExperianCrosscoreAuthResponseDTO {
        try {
            $headers = array_merge($this->makeHeaders(), ['X-User-Domain' => 'publicguardian.com']);

            $response = $this->client->request(
                'POST',
                '/oauth2/experianone/v1/token',
                [
                    'headers' => $headers,
                    'json' => $experianCrosscoreAuthRequestDTO->toArray()
                ]
            );

            $responseArray = json_decode($response->getBody()->getContents(), true);

            return new ExperianCrosscoreAuthResponseDTO(
                $responseArray['access_token'],
                $responseArray['refresh_token'],
                $responseArray['issued_at'],
                $responseArray['expires_in'],
                $responseArray['token_type']
            );
        } catch (\Exception $exception) {
            throw new ExperianCrosscoreAuthApiException($exception->getMessage());
        }
    }
}

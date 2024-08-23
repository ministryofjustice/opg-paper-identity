<?php

declare(strict_types=1);

namespace Application\Services\Experian\AuthApi;

use Application\Aws\Secrets\AwsSecret;
use Application\Services\Experian\AuthApi\DTO\ExperianCrosscoreAuthRequestDTO;
use Application\Services\Experian\AuthApi\DTO\ExperianCrosscoreAuthResponseDTO;
use Application\Services\Experian\AuthApi\DTO\ExperianCrosscoreRefreshRequestDTO;
use Application\Services\Experian\AuthApi\ExperianCrosscoreAuthApiException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Ramsey\Uuid\Uuid;

class ExperianCrosscoreAuthApiService
{
    public function __construct(
        private readonly Client $client
    ) {
    }

    private function generateXCorrelationId(): string
    {
        return Uuid::uuid4()->toString();
    }

    private function getAuthUrl(): string
    {
        return getenv('EXPERIAN_AUTH_URL');
    }

    public function makeHeaders(): array
    {
        return [
            'Content-Type'     => 'application/json',
            'X-Correlation-Id' => $this->generateXCorrelationId(),
            'X-User-Domain'    => getenv('EXPERIAN_DOMAIN')
        ];
    }

    public function authenticate(bool $refresh = false): void
    {
        $credentials = $this->getCredentials();

        $tokenResponse = $this->getToken($credentials);

        $this->cacheTokenResponse($tokenResponse);
    }

    private function cacheTokenResponse(ExperianCrosscoreAuthResponseDTO $experianCrosscoreAuthResponseDTO): void
    {

    }

    private function retrieveCachedTokenResponse(): ExperianCrosscoreAuthResponseDTO
    {
        return new ExperianCrosscoreAuthResponseDTO();
    }

    /**
     * @throws ExperianCrosscoreAuthApiException
     */
    public function getCredentials(): ExperianCrosscoreAuthRequestDTO
    {
        try {
            return new ExperianCrosscoreAuthRequestDTO(
                (new AwsSecret('experian-crosscore/username'))->getValue(),
                (new AwsSecret('experian-crosscore/password'))->getValue(),
                (new AwsSecret('experian-crosscore/client-id'))->getValue(),
                (new AwsSecret('experian-crosscore/client-secret'))->getValue()
            );
        } catch (\Exception $exception) {
            throw new ExperianCrosscoreAuthApiException($exception->getMessage());
        }
    }

    /**
     * @throws GuzzleException
     * @throws ExperianCrosscoreAuthApiException
     */
    public function getToken(
        ExperianCrosscoreAuthRequestDTO $experianCrosscoreAuthRequestDTO
    ): ExperianCrosscoreAuthResponseDTO {
        try {
            $response = $this->client->post(
                $this->getAuthUrl(),
                [
                    'headers' => $this->makeHeaders(),
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

    public function refreshToken(
        ExperianCrosscoreRefreshRequestDTO $experianCrosscoreRefreshRequestDTO
    ): ExperianCrosscoreAuthResponseDTO {
        try {
            $response = $this->client->post(
                $this->getAuthUrl(),
                [
                    'headers' => $this->makeHeaders(),
                    'json' => $experianCrosscoreRefreshRequestDTO->toArray()
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

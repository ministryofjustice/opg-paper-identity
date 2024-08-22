<?php

declare(strict_types=1);

namespace Application\Services;

use Application\Services\DTO\ExperianCrosscoreAuthRequestDTO;
use GuzzleHttp\Client;
use Application\Services\DTO\ExperianCrosscoreAuthResponseDTO;
use Ramsey\Uuid\Uuid;
use Application\Exceptions\ExperianCrosscoreAPIException;

class ExperianCrosscoreApiService
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
            'X-User-Domain'    => 'DOMAIN'
        ];
    }

    public function makeRequestBody(): array
    {
        return [
            'username' => 'USER@DOMAIN',
            'password' => 'PASSWORD',
            'client_id' => 'CLIENT_ID',
            'client_secret' => 'CLIENT_SECRET'
        ];
    }

    public function authenticate(
        ExperianCrosscoreAuthRequestDTO $experianCrosscoreAuthRequestDTO
    ): ExperianCrosscoreAuthResponseDTO {
        try {
            $response = $this->client->post(
                $this->getAuthUrl(),
                [
                    'headers' => $this->makeHeaders(),
                    'json' => $this->makeRequestBody()
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
            throw new ExperianCrosscoreAPIException($exception->getMessage());
        }
    }
}

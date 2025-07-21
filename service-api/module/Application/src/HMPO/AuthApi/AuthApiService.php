<?php

declare(strict_types=1);

namespace Application\HMPO\AuthApi;

use Application\Cache\ApcHelper;
use Application\HMPO\AuthApi\DTO\RequestDTO;
use Application\HMPO\AuthApi\DTO\ResponseDTO;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Exception\ClientException;

class AuthApiService
{
    public function __construct(
        private readonly Client $client,
        private readonly ApcHelper $apcHelper,
        private readonly LoggerInterface $logger,
        private readonly RequestDTO $hmpoAuthRequestDTO,
    ) {
    }

    private const HMPO_AUTH_ENDPOINT = '/auth/token';

    public function makeHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'X-API-Key' => 'X-API-Key-X-API-Key-X-API-Key-X-API-Key', # get this from an env
            'X-REQUEST-ID' => '05ecc9c8-6259-11f0-8ce0-325096b39f47', # should we just generate and log for ourselves??
            'X-DVAD-NETWORK-TYPE' => 'api', # are we hardcoding this since it wont change?
            'User-Agent' => 'hmpo-opg-client', # should we get this from an env variable as well?
        ];
    }

    /**
     * @throws GuzzleException
     * @throws AuthApiException
     */
    public function authenticate(): ResponseDTO
    {
        $credentials = $this->hmpoAuthRequestDTO;

        $tokenResponse = $this->getToken($credentials);

        $this->cacheTokenResponse($tokenResponse);

        return $tokenResponse;
    }

    private function cacheTokenResponse(
        ResponseDTO $hmpoAuthResponseDTO,
    ): void {
        $this->apcHelper->setValue(
            'hmpo_access_token',
            json_encode([
                'access_token' => $hmpoAuthResponseDTO->accessToken(),
                'time' => (int)(new \DateTime())->format('U') + (int)$hmpoAuthResponseDTO->expiresIn()
            ])
        );
    }

    /**
     * @throws GuzzleException
     * @throws AuthApiException
     */
    public function retrieveCachedTokenResponse(): string
    {
        $cachedToken = $this->apcHelper->getValue('hmpo_access_token');

        if (! $cachedToken) {
            return $this->authenticate()->accessToken();
        }

        $tokenResponse = json_decode($cachedToken, true);

        if (is_null($tokenResponse) || ((int)$tokenResponse['time'] + 3500) > time()) {
            return $tokenResponse['access_token'];
        } else {
            return $this->authenticate()->accessToken();
        }
    }

    /**
     * @throws GuzzleException
     * @throws AuthApiException
     */
    private function getToken(
        RequestDTO $hmpoAuthRequestDTO
    ): ResponseDTO {
        try {
            $response = $this->client->request(
                'POST',
                self::HMPO_AUTH_ENDPOINT,
                [
                    'headers' => $this->makeHeaders(),
                    'json' => $hmpoAuthRequestDTO->toArray()
                ],
            );

            $responseArray = json_decode($response->getBody()->getContents(), true);

            return new ResponseDTO(
                $responseArray['access_token'],
                $responseArray['expires_in'],
                $responseArray['refresh_expires_in'],
                $responseArray['refresh_token'] ?? null,
                $responseArray['token_type'],
            );
        } catch (ClientException $clientException) {
            $response = $clientException->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            $this->logger->error('GuzzleHmpoAuthApiException: ' . $responseBodyAsString);
            throw new AuthApiException($clientException->getMessage());
        } catch (\Exception $exception) {
            $this->logger->error('GuzzleHmpoAuthApiException: ' . $exception->getMessage());
            throw new AuthApiException($exception->getMessage());
        }
    }
}

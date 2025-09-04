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
use Ramsey\Uuid\Uuid;

class AuthApiService
{
    public function __construct(
        private readonly Client $client,
        private readonly ApcHelper $apcHelper,
        private readonly LoggerInterface $logger,
        private readonly RequestDTO $hmpoAuthRequestDTO,
        private array $headerOptions,
    ) {
    }

    private const HMPO_AUTH_ENDPOINT = '/auth/token';
    private const CACHE_NAME = 'hmpo_access_token';

    public function makeHeaders(): array
    {
        return [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'X-API-Key' => $this->headerOptions['X-API-Key'],
            'X-REQUEST-ID' => Uuid::uuid1()->toString(),
            'X-DVAD-NETWORK-TYPE' => 'API',
            'User-Agent' => 'hmpo-opg-client',
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
            self::CACHE_NAME,
            json_encode([
                'access_token' => $hmpoAuthResponseDTO->accessToken(),
                'time' => (int)(new \DateTime())->format('U') + $hmpoAuthResponseDTO->expiresIn()
            ])
        );
    }

    /**
     * @throws GuzzleException
     * @throws AuthApiException
     */
    public function retrieveCachedTokenResponse(): string
    {
        $cachedToken = $this->apcHelper->getValue(self::CACHE_NAME);

        if (! $cachedToken) {
            return $this->authenticate()->accessToken();
        }

        $tokenResponse = json_decode($cachedToken, true);

        if (is_null($tokenResponse) || ((int)$tokenResponse['time']) > time()) {
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
            $headers = $this->makeHeaders();
            $this->logger->info("Authenticating with HMPO", ["requestId" => $headers['X-REQUEST-ID']]);
            $response = $this->client->request(
                'POST',
                self::HMPO_AUTH_ENDPOINT,
                [
                    'headers' => $headers,
                    'form_params' => $hmpoAuthRequestDTO->toArray()
                ],
            );

            $responseArray = json_decode($response->getBody()->getContents(), true);
            $this->logger->info("Successfully authenticated with HMPO");

            return new ResponseDTO(
                $responseArray['access_token'],
                $responseArray['expires_in'],
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

<?php

declare(strict_types=1);

namespace Application\Services\Auth;

use Application\Cache\ApcHelper;
use Application\Services\Auth\DTO\RequestDTO;
use Application\Services\Auth\DTO\ResponseDTO;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

abstract class AuthApiService
{
    public string $responseDtoClass;
    public const AUTH_ENDPOINT = '';
    public const CACHE_NAME = '';
    abstract protected function makeHeaders(): array;

    public function __construct(
        private readonly Client $client,
        private readonly ApcHelper $apcHelper,
        private readonly LoggerInterface $logger,
        private readonly RequestDTO $requestDTO,
        public array $headerOptions,
    ) {
        if (static::AUTH_ENDPOINT === '') {
            throw new Exception("const AUTH_ENDPOINT must be defined in child class");
        }
        if (static::CACHE_NAME === '') {
            throw new Exception("const CACHE_NAME must be defined in child class");
        }
    }

    /**
     * @throws GuzzleException
     * @throws AuthApiException
     */
    public function authenticate(): ResponseDTO
    {
        $credentials = $this->requestDTO;
        $tokenResponse = $this->getToken($credentials);
        $this->cacheTokenResponse($tokenResponse);

        return $tokenResponse;
    }

    private function cacheTokenResponse(
        ResponseDTO $responseDTO,
    ): void {
        $this->apcHelper->setValue(
            static::CACHE_NAME,
            json_encode([
                'access_token' => $responseDTO->accessToken(),
                'time' => (int)(new \DateTime())->format('U') + (int)$responseDTO->expiresIn()
            ])
        );
    }

    /**
     * @throws GuzzleException
     * @throws AuthApiException
     */
    public function retrieveCachedTokenResponse(): string
    {
        $cachedToken = $this->apcHelper->getValue(static::CACHE_NAME);
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
        RequestDTO $requestDTO
    ): ResponseDTO {
        try {
            $response = $this->client->request(
                'POST',
                static::AUTH_ENDPOINT,
                [
                    'headers' => $this->makeHeaders(),
                    'form_params' => $requestDTO->toArray()
                ],
            );
            $responseArray = json_decode($response->getBody()->getContents(), true);
            return new $this->responseDtoClass($responseArray);
        } catch (ClientException $clientException) {
            $response = $clientException->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            $this->logger->error('GuzzleAuthException: ' . $responseBodyAsString);
            throw new AuthApiException($clientException->getMessage());
        } catch (\Exception $exception) {
            $this->logger->error('GuzzleAuthException: ' . $exception->getMessage());
            throw new AuthApiException($exception->getMessage());
        }
    }
}

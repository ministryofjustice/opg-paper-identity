<?php

declare(strict_types=1);

namespace Application\Services\Auth;

use Application\Cache\ApcHelper;
use Application\Services\Auth\DTO\RequestDTO;
use Application\Services\Auth\DTO\ResponseDTO;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Psr\Log\LoggerInterface;

// TODO: make experian auth use this too??
abstract class AuthApiService
{
    abstract protected function makeHeaders(): array;

    public function __construct(
        private readonly Client $client,
        private readonly ApcHelper $apcHelper,
        private readonly LoggerInterface $logger,
        private readonly RequestDTO $requestDTO,
        private readonly string $authEndpoint,
        private readonly string $cacheName,
        public readonly ?array $headerOptions = null,
    ) {
    }

    /**
     * @throws AuthApiException
     */
    public function retrieveCachedTokenResponse(): string
    {
        $cachedToken = $this->apcHelper->getValue($this->cacheName);
        if (! $cachedToken) {
            return $this->authenticate()->accessToken();
        }

        $tokenResponse = json_decode($cachedToken, true);
        if (((int)$tokenResponse['time']) > time()) {
            return $tokenResponse['access_token'];
        } else {
            return $this->authenticate()->accessToken();
        }
    }

    /**
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
            $this->cacheName,
            json_encode([
                'access_token' => $responseDTO->accessToken(),
                'time' => (int)(new \DateTime())->format('U') + (int)$responseDTO->expiresIn()
            ])
        );
    }

    /**
     * @throws AuthApiException
     */
    private function getToken(
        RequestDTO $requestDTO
    ): ResponseDTO {
        try {
            $response = $this->client->request(
                'POST',
                $this->authEndpoint,
                [
                    'headers' => $this->makeHeaders(),
                    'form_params' => $requestDTO->toArray()
                ],
            );
            $contents = $response->getBody()->getContents();
            $responseArray = json_decode($contents, true);
            return new ResponseDTO(
                $responseArray['access_token'],
                $responseArray['expires_in'],
                $responseArray['token_type'],
            );
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

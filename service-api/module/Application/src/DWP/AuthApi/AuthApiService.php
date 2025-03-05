<?php

declare(strict_types=1);

namespace Application\DWP\AuthApi;

use Application\Cache\ApcHelper;
use Application\DWP\AuthApi\DTO\RequestDTO;
use Application\DWP\AuthApi\DTO\ResponseDTO;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Application\Aws\SsmHandler;
use GuzzleHttp\Exception\ClientException;
use Application\Aws\Secrets\AwsSecret;

class AuthApiService
{
    public function __construct(
        private readonly Client $client,
        private readonly ApcHelper $apcHelper,
        private readonly LoggerInterface $logger,
        private readonly RequestDTO $dwpAuthRequestDTO,
    ) {
    }

    private const DWP_AUTH_ENDPOINT = '/citizen-information/oauth2/token';

    public function makeHeaders(): array
    {
        return [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
    }

    /**
     * @throws GuzzleException
     * @throws AuthApiException
     */
    public function authenticate(): ResponseDTO
    {
        $credentials = $this->dwpAuthRequestDTO;

        $tokenResponse = $this->getToken($credentials);

        $this->cacheTokenResponse($tokenResponse);

        return $tokenResponse;
    }

    private function cacheTokenResponse(
        ResponseDTO $dwpAuthResponseDTO,
    ): void {
        $this->apcHelper->setValue(
            'dwp_access_token',
            json_encode([
                'access_token' => $dwpAuthResponseDTO->accessToken(),
                'time' => (int)(new \DateTime())->format('U') + (int)$dwpAuthResponseDTO->expiresIn()
            ])
        );
    }

    /**
     * @throws GuzzleException
     * @throws AuthApiException
     */
    public function retrieveCachedTokenResponse(): string
    {
        $cachedToken = $this->apcHelper->getValue('dwp_access_token');

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
        RequestDTO $dwpAuthRequestDTO
    ): ResponseDTO {
        try {
            $response = $this->client->request(
                'POST',
                self::DWP_AUTH_ENDPOINT,
                [
                    'headers' => $this->makeHeaders(),
                    'form_params' => $dwpAuthRequestDTO->toArray()
                ],
            );

            $responseArray = json_decode($response->getBody()->getContents(), true);

            return new ResponseDTO(
                $responseArray['access_token'],
                $responseArray['expires_in'],
                $responseArray['token_type'],
            );
        } catch (ClientException $clientException) {
            $response = $clientException->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            $this->logger->error('GuzzleDwpAuthApiException: ' . $responseBodyAsString);
            throw new AuthApiException($clientException->getMessage());
        } catch (\Exception $exception) {
            $this->logger->error('GuzzleDwpAuthApiException: ' . $exception->getMessage());
            throw new AuthApiException($exception->getMessage());
        }
    }
}

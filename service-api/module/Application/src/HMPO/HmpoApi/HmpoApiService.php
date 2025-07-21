<?php

declare(strict_types=1);

namespace Application\HMPO\HmpoApi;

use Application\HMPO\AuthApi\AuthApiException;
use Application\HMPO\AuthApi\AuthApiService;
use Application\HMPO\HmpoApi\DTO\ValidatePassportRequestDTO;
use Application\HMPO\HmpoApi\DTO\ValidatePassportResponseDTO;
use Application\HMPO\HmpoApi\HmpoApiException;
use Application\Model\Entity\CaseData;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Laminas\Http\Response;
use Psr\Log\LoggerInterface;

class HmpoApiService
{
    private int $authCount = 0;

    public function __construct(
        private Client $guzzleClient,
        private AuthApiService $authApiService,
        private LoggerInterface $logger,
    ) {
    }

    private const HMPO_GRAPHQL_ENDPOINT = '/graphql';

    /**
     * @throws GuzzleException
     * @throws AuthApiException
     * @throws HmpoApiException
     */
    public function makeHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'X-API-Key' => 'X-API-Key-X-API-Key-X-API-Key-X-API-Key', # get this from an env
            'X-REQUEST-ID' => '05ecc9c8-6259-11f0-8ce0-325096b39f47', # should we just generate and log for ourselves??
            'X-DVAD-NETWORK-TYPE' => 'api', # are we hardcoding this since it wont change?
            'User-Agent' => 'hmpo-opg-client', # should we get this from an env variable as well?
            'Authorization' => sprintf('Bearer %s', $this->authApiService->retrieveCachedTokenResponse())
        ];
    }

    public function validatePassport(CaseData $caseData, int $passportNumber): bool
    {
        $request = new ValidatePassportRequestDTO($caseData, $passportNumber);
        $result = new ValidatePassportResponseDTO($this->getValidatePassportResponse($request));

        return $result->isValid();
    }


    public function getValidatePassportResponse(ValidatePassportRequestDTO $request): array
    {
        $this->authCount++;
        try {
            $response = $this->guzzleClient->request(
                'POST',
                self::HMPO_GRAPHQL_ENDPOINT,
                [
                    'headers' => $this->makeHeaders(),
                    'json' => $request->constructValidatePassportRequestBody(),
                ]
            );
        } catch (ClientException $clientException) {
            if (
                $clientException->getResponse()->getStatusCode() == Response::STATUS_CODE_401 &&
                $this->authCount < 2
            ) {
                $this->authApiService->authenticate();
                $response = $this->getvalidatePassportResponse($request);
            } else {
                $response = $clientException->getResponse();
                $responseBodyAsString = $response->getBody()->getContents();
                $this->logger->error('GuzzleHmpoApiException: ' . $responseBodyAsString);
                throw $clientException;
            }
        } catch (\Exception $exception) {
            $this->logger->error('HmpoApiException : ' . $exception->getMessage(), ['exception' => $exception]);
            throw new HmpoApiException($exception->getMessage());
        }

        return json_decode($response->getBody()->getContents(), true);;
    }
}
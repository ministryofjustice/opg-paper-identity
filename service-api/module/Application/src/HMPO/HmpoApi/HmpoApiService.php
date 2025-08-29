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
use Ramsey\Uuid\Uuid;

class HmpoApiService
{
    private int $authCount = 0;

    public function __construct(
        private Client $guzzleClient,
        private AuthApiService $authApiService,
        private LoggerInterface $logger,
        private array $headerOptions,
    ) {
        if (! array_key_exists('X-API-Key', $headerOptions)) {
            throw new HmpoApiException('X-API-Key must be present in headerOptions');
        }
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
            'X-API-Key' => $this->headerOptions['X-API-Key'],
            'X-REQUEST-ID' => strval(Uuid::uuid1()),
            'X-DVAD-NETWORK-TYPE' => 'api',
            'User-Agent' => 'hmpo-opg-client',
            'Authorization' => sprintf('Bearer %s', $this->authApiService->retrieveCachedTokenResponse())
        ];
    }

    public function validatePassport(CaseData $caseData, int $passportNumber): bool
    {
        $request = new ValidatePassportRequestDTO($caseData, $passportNumber);
        $result = new ValidatePassportResponseDTO($this->getValidatePassportResponse($request));
        $this->logger->info("Successfully validated against HMPO graphQL endpoint");

        return $result->isValid();
    }


    public function getValidatePassportResponse(ValidatePassportRequestDTO $request): array
    {
        $this->authCount++;
        $headers = $this->makeHeaders();
        try {
            $this->logger->info("hitting hmpo graphQL endpoint");
            $response = $this->guzzleClient->request(
                'POST',
                self::HMPO_GRAPHQL_ENDPOINT,
                [
                    'headers' => $headers,
                    'json' => $request->constructValidatePassportRequestBody(),
                ]
            );
        } catch (ClientException $clientException) {
            if (
                $clientException->getResponse()->getStatusCode() == Response::STATUS_CODE_401 &&
                $this->authCount < 2
            ) {
                $this->authApiService->authenticate();
                return $this->getvalidatePassportResponse($request);
            } else {
                $response = $clientException->getResponse();
                $responseBodyAsString = $response->getBody()->getContents();
                $this->logger->error(
                    "GuzzleHmpoApiException on requestId: {$headers['X-REQUEST-ID']}, response: $responseBodyAsString"
                );
                throw $clientException;
            }
        } catch (\Exception $exception) {
            $this->logger->error('HmpoApiException : ' . $exception->getMessage(), ['exception' => $exception]);
            throw new HmpoApiException($exception->getMessage());
        }

        return json_decode($response->getBody()->getContents(), true);
    }
}

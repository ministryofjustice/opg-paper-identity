<?php

declare(strict_types=1);

namespace Application\DWP\DwpApi;

use Application\DWP\AuthApi\AuthApiException;
use Application\DWP\AuthApi\AuthApiService;
use Application\DWP\DwpApi\DTO\CitizenRequestDTO;
use Application\DWP\DwpApi\DTO\CitizenResponseDTO;
use Application\DWP\DwpApi\DTO\DetailsRequestDTO;
use Application\DWP\DwpApi\DTO\DetailsResponseDTO;
use Application\DWP\DwpApi\DwpApiException;
use Application\Model\Entity\CaseData;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Laminas\Http\Response;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Application\Aws\Secrets\AwsSecret;

class DwpApiService
{
    private int $authCount = 0;

    private string $correlationUuid = "";
    public function __construct(
        private Client $guzzleClient,
        private AuthApiService $authApiService,
        private LoggerInterface $logger,
        private array $headerOptions,
    ) {
    }

    private const DWP_MATCH_ENDPOINT = '/capi/v2/citizens/match';
    private const DWP_DETAILS_ENDPOINT = '/capi/v2/citizens/';

    /**
     * @throws GuzzleException
     * @throws AuthApiException
     */
    public function makeHeaders(array $optionalHeaders = []): array
    {
        return array_merge([
            'Content-Type' => 'application/json',
            'Context' => $this->headerOptions['context'],
            'Authorization' => sprintf(
                'Bearer %s',
                $this->authApiService->retrieveCachedTokenResponse()
            ),
            'Correlation-Id' => $this->correlationUuid,
            'Policy-Id' => $this->headerOptions['policy_id'],
            'Instigating-User-Id' => '',
        ], $optionalHeaders);
    }

    public function validateNino(CaseData $caseData, string $nino, string $correlationUuid): bool
    {
        $this->correlationUuid = $correlationUuid;

        try {
            $citizenResponseDTO = $this->makeCitizenMatchRequest(
                new CitizenRequestDTO($caseData, $nino)
            );

            $this->logger->info('DwpMatchResponse: ' . "($this->correlationUuid) ", $citizenResponseDTO->toArray());

            $detailsResponseDTO = $this->makeCitizenDetailsRequest(
                new DetailsRequestDTO($citizenResponseDTO->id()),
                $nino
            );

            $this->logger->info('DwpDetailsResponse: ' . "($this->correlationUuid) ", $detailsResponseDTO->toArray());

            return $this->compareRecords($caseData, $detailsResponseDTO, $citizenResponseDTO, $nino);
        } catch (\Exception $exception) {
            $this->logger->error('DwpApiException: ' . "($this->correlationUuid) " . $exception->getMessage());
            throw new DwpApiException($exception->getMessage());
        }
    }

    public function compareRecords(
        CaseData $caseData,
        DetailsResponseDTO $detailsResponseDTO,
        CitizenResponseDTO $citizenResponseDTO,
        string $nino
    ): bool {
        if ($caseData->idMethodIncludingNation) {
            /** @psalm-suppress PossiblyNullArgument */
            $submittedNino = strtoupper(
                preg_replace(
                    '/(\s+)|(-)/',
                    '',
                    $nino
                )
            );

            /** @psalm-suppress PossiblyNullArgument */
            $returnedNino = strtoupper(
                preg_replace(
                    '/(\s+)|(-)/',
                    '',
                    $detailsResponseDTO->nino()
                )
            );

            if ($citizenResponseDTO->type() !== 'MatchResult') {
                $this->logger->info("Match Result missing from response.");
                return false;
            }

            if ($submittedNino !== $returnedNino) {
                $this->logger->info("Submitted NINO does not match NINO in details response.");
                return false;
            }

            return true;
        } else {
            throw new DwpApiException('National Insurance Number not set.');
        }
    }

    /**
     * @throws GuzzleException
     * @throws DwpApiException
     */
    public function makeCitizenMatchRequest(
        CitizenRequestDTO $citizenRequestDTO,
    ): CitizenResponseDTO {
        $this->authCount++;
        try {
            $response = $this->guzzleClient->request(
                'POST',
                self::DWP_MATCH_ENDPOINT,
                [
                    'headers' => $this->makeHeaders(),
                    'json' => $citizenRequestDTO->constructCitizenRequestBody()
                ]
            );
            $responseArray = json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $clientException) {
            if (
                $clientException->getResponse()->getStatusCode() == Response::STATUS_CODE_401 &&
                $this->authCount < 2
            ) {
                $this->authApiService->authenticate();
                $responseArray = $this->makeCitizenMatchRequest($citizenRequestDTO);
            } else {
                $response = $clientException->getResponse();
                $responseBodyAsString = $response->getBody()->getContents();
                $this->logger->error('GuzzleDwpApiException: ' . $responseBodyAsString);
                throw $clientException;
            }
        } catch (\Exception $exception) {
            throw new DwpApiException($exception->getMessage());
        }
        return new CitizenResponseDTO(
            $responseArray
        );
    }

    /**
     * @throws GuzzleException
     * @throws DwpApiException
     */
    public function makeCitizenDetailsRequest(
        DetailsRequestDTO $detailsRequestDTO,
        string $nino
    ): DetailsResponseDTO {
        $this->authCount++;
        try {
            $uri = self::DWP_DETAILS_ENDPOINT . $detailsRequestDTO->id();
            $response = $this->guzzleClient->request(
                'GET',
                $uri,
                [
                    'headers' => $this->makeHeaders(['Access-Level => 4']),
                ]
            );
            $responseArray = json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $clientException) {
            if (
                $clientException->getResponse()->getStatusCode() == Response::STATUS_CODE_401 &&
                $this->authCount < 2
            ) {
                $this->authApiService->authenticate();
                $responseArray = $this->makeCitizenDetailsRequest($detailsRequestDTO, $nino);
            } else {
                $this->logger->error('GuzzleDwpApiException: ' . $clientException->getMessage());
                throw $clientException;
            }
        } catch (\Exception $exception) {
            $this->logger->error("DwpApiException: " . $exception->getMessage());
            throw new DwpApiException("DwpApiException: " . $exception->getMessage());
        }

        return new DetailsResponseDTO(
            $responseArray
        );
    }
}

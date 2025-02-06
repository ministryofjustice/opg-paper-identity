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

use function Amp\Promise\all;

class DwpApiService
{
    private int $authCount = 0;
    public function __construct(
        private Client $guzzleClient,
        private AuthApiService $authApiService,
        private LoggerInterface $logger,
        private string $detailsPath,
        private string $matchPath,
    ) {
    }

    /**
     * @throws GuzzleException
     * @throws AuthApiException
     */
    public function makeHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Context' => 'application/process',
            'Authorization' => sprintf(
                'Bearer %s',
                $this->authApiService->retrieveCachedTokenResponse()
            ),
            'Correlation-Id' => '',
            'Policy-Id' => '',
            'Instigating-User-Id' => ''
        ];
    }

    public function validateNino(CaseData $caseData, string $nino): bool
    {
        try {
            $citizenResponseDTO = $this->makeCitizenMatchRequest(
                new CitizenRequestDTO($caseData, $nino)
            );
            $detailsResponseDTO = $this->makeCitizenDetailsRequest(
                new DetailsRequestDTO($citizenResponseDTO->id()),
                $nino
            );

            return $this->compareRecords($caseData, $detailsResponseDTO, $citizenResponseDTO, $nino);
        } catch (\Exception $exception) {
            $this->logger->error('DwpApiException: ' . $exception->getMessage());
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

            if (
                $citizenResponseDTO->matchScenario() !== 'Matched on NINO' ||
                $detailsResponseDTO->verified() !== 'verified' ||
                $submittedNino !== $returnedNino
            ) {
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
        $responseArray = [];
        try {
            $postBody = $this->constructCitizenRequestBody($citizenRequestDTO);
            $this->logger->error('MATCH_POSTBODY: ', $postBody);

            $response = $this->guzzleClient->request(
                'POST',
                $this->matchPath,
                [
                    'headers' => $this->makeHeaders(),
                    'json' => $postBody
                ]
            );
            $responseArray = json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $clientException) {
            if (
                $clientException->getResponse()->getStatusCode() == Response::STATUS_CODE_401 &&
                $this->authCount < 2
            ) {
                $this->authApiService->authenticate();
                $this->makeCitizenMatchRequest($citizenRequestDTO);
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

    public function constructCitizenRequestBody(
        CitizenRequestDTO $citizenRequestDTO
    ): array {
        try {
            return [
                "jsonapi" => [
                    "version" => "1.0"
                ],
                "data" => [
                    "type" => "Match",
                    "attributes" => [
                        "dateOfBirth" => $citizenRequestDTO->dob(),
                        "ninoFragment" => $this->makeNinoFragment($citizenRequestDTO->nino()),
                        "firstName" => $citizenRequestDTO->firstName(),
                        "lastName" => $citizenRequestDTO->lastName(),
                        "postcode" => $this->makeFormattedPostcode($citizenRequestDTO->postcode()),
                        "contactDetails" => [
                            ""
                        ]
                    ]
                ]
            ];
        } catch (\Exception $exception) {
            throw new DwpApiException($exception->getMessage());
        }
    }

    public function makeNinoFragment(string $nino): string
    {
        $nino = str_replace(" ", "", $nino);

        return substr($nino, strlen($nino) - 4, strlen($nino));
    }

    public function makeFormattedPostcode(string $postcode): string
    {
        $cleanPostcode = preg_replace("/[^A-Za-z0-9]/", '', $postcode);
        /**
         * @psalm-suppress PossiblyNullArgument
         */
        $cleanPostcode = strtoupper($cleanPostcode);
        return substr($cleanPostcode, 0, -3) . " " . substr($cleanPostcode, -3);
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
        $responseArray = [];
        try {
            $uri = sprintf($this->detailsPath, $detailsRequestDTO->id());

            $response = $this->guzzleClient->request(
                'GET',
                $uri,
                [
                    'headers' => $this->makeHeaders(),
                ]
            );
            $responseArray = json_decode($response->getBody()->getContents(), true);
            $this->logger->info("DWP_RESPONSE: " . json_encode($responseArray, JSON_THROW_ON_ERROR));
        } catch (ClientException $clientException) {
            if (
                $clientException->getResponse()->getStatusCode() == Response::STATUS_CODE_401 &&
                $this->authCount < 2
            ) {
                $this->authApiService->authenticate();
                $this->makeCitizenDetailsRequest($detailsRequestDTO, $nino);
            } else {
                $response = $clientException->getResponse();
                $responseBodyAsString = $response->getBody()->getContents();
                $this->logger->error('GuzzleDwpApiException: ' . $responseBodyAsString);
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

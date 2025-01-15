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
    private string $matchPath;
    private string $detailsPath;
    private $authCount = 0;
    public function __construct(
        private Client $guzzleClientMatch,
        private Client $guzzleClientDetails,
        private AuthApiService $authApiService,
        private LoggerInterface $logger
    ) {
        $this->matchPath = (new AwsSecret('dwp/citizen-match-endpoint'))->getValue();
        $this->detailsPath = (new AwsSecret('dwp/citizen-details-endpoint'))->getValue();
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

    public function validateNino(CaseData $caseData): array
    {
        try {
            $citizenResponseDTO = $this->makeCitizenMatchRequest(
                new CitizenRequestDTO($caseData)
            );
            $detailsResponseDTO = $this->makeCitizenDetailsRequest(
                new DetailsRequestDTO($citizenResponseDTO->id())
            );
            return $this->compareRecords($caseData, $detailsResponseDTO, $citizenResponseDTO);
        } catch (\Exception $exception) {
            $this->logger->error('DwpApiException: ' . $exception->getMessage());
            throw new DwpApiException($exception->getMessage());
        }
    }

    public function compareRecords(
        CaseData $caseData,
        DetailsResponseDTO $detailsResponseDTO,
        CitizenResponseDTO $citizenResponseDTO
    ): array {
        if (
            $citizenResponseDTO->matchScenario() !== 'Matched on NINO' ||
            $detailsResponseDTO->verified() !== 'verified'
        ) {
            return [
                $caseData->idMethodIncludingNation->id_value,
                'NO_MATCH',
                Response::STATUS_CODE_200
            ];
        }
        return [
            $caseData->idMethodIncludingNation->id_value,
            'PASS',
            Response::STATUS_CODE_200
        ];
    }

    /**
     * @throws GuzzleException
     * @throws DwpApiException
     * @psalm-suppress InvalidReturnType
     */
    public function makeCitizenMatchRequest(
        CitizenRequestDTO $citizenRequestDTO
    ): CitizenResponseDTO {
        $this->authCount++;
        try {
            $postBody = $this->constructCitizenRequestBody($citizenRequestDTO);

            $response = $this->guzzleClientMatch->request(
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
                        "postcode" => $citizenRequestDTO->postcode(),
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

    /**
     * @throws GuzzleException
     * @throws DwpApiException
     * @psalm-suppress InvalidReturnType
     */
    public function makeCitizenDetailsRequest(
        DetailsRequestDTO $detailsRequestDTO
    ): DetailsResponseDTO {
        $this->authCount++;
        try {
            $uri = sprintf($this->detailsPath, $detailsRequestDTO->id());

            $response = $this->guzzleClientDetails->request(
                'GET',
                $uri,
                [
                    'headers' => $this->makeHeaders(),
                ]
            );
            $responseArray = json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $clientException) {
            if (
                $clientException->getResponse()->getStatusCode() == Response::STATUS_CODE_401 &&
                $this->authCount < 2
            ) {
                $this->authApiService->authenticate();
                $this->makeCitizenDetailsRequest($detailsRequestDTO);
            } else {
                $response = $clientException->getResponse();
                $responseBodyAsString = $response->getBody()->getContents();
                $this->logger->error('GuzzleDwpApiException: ' . $responseBodyAsString);
                throw $clientException;
            }
        } catch (\Exception $exception) {
            throw new DwpApiException($exception->getMessage());
        }
        return new DetailsResponseDTO(
            $responseArray
        );
    }
}

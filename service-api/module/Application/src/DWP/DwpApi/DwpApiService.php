<?php

declare(strict_types=1);

namespace Application\DWP\DwpApi;

use Application\DWP\AuthApi\AuthApiException;
use Application\DWP\AuthApi\AuthApiService;
use Application\DWP\DwpApi\DTO\CitizenRequestDTO;
use Application\DWP\DwpApi\DTO\CitizenResponseDTO;
use Application\DWP\DwpApi\DwpApiException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Laminas\Http\Response;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

class DwpApiService
{
    private $authCount = 0;

    function __construct(
        private Client          $guzzleClientCitizen,
        private Client          $guzzleClientMatch,
        private AuthApiService  $authApiService,
        private LoggerInterface $logger,
        private array           $config
    )
    {
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


    /**
     * @throws GuzzleException
     * @throws DwpApiException
     * @psalm-suppress InvalidReturnType
     */
    public function makeCitizenRequest(
        CitizenRequestDTO $citizenRequestDTO
    ): CitizenResponseDTO
    {
        $this->authCount++;
        try {
            $postBody = $this->constructCitizenRequestBody($citizenRequestDTO);

            $response = $this->guzzleClientCitizen->request(
                'POST',
                '3',
                [
                    'headers' => $this->makeHeaders(),
                    'json' => $postBody
                ]
            );

            $responseArray = json_decode($response->getBody()->getContents(), true);

            return new CitizenResponseDTO(
                $responseArray
            );
        } catch (ClientException $clientException) {
            if ($clientException->getResponse()->getStatusCode() == Response::STATUS_CODE_401 && $this->authCount < 2) {
                $this->authApiService->authenticate();
                $this->makeCitizenRequest($citizenRequestDTO);
            } else {
                $response = $clientException->getResponse();
                $responseBodyAsString = $response->getBody()->getContents();
                $this->logger->error('GuzzleDwpApiException: ' . $responseBodyAsString);
                throw $clientException;
            }
        } catch (\Exception $exception) {
            throw new DwpApiException($exception->getMessage());
        }
    }

    public function constructCitizenRequestBody(
        CitizenRequestDTO $citizenRequestDTO
    ): array
    {
//        $requestUuid = Uuid::uuid4()->toString();
//        $personId = $this->makePersonId($experianCrosscoreFraudRequestDTO);
//        $nameId = $this->makePersonId($experianCrosscoreFraudRequestDTO, true);
//        $addressDTO = $experianCrosscoreFraudRequestDTO->address();

        return [
            "jsonapi" => [
                "version" => "1.0"
            ],
            "data" => [
                "type" => "Match",
                "attributes" => [
                    "dateOfBirth" => "1986-09-03",
                    "ninoFragment" => "233C",
                    "firstName" => "Lee",
                    "lastName" => "Manthrope",
                    "postcode" => "SO15 3AA",
                    "contactDetails" => [
                        "lee.manthrope@example.com"
                    ]
                ]
            ]
        ];


    }

    public function makeNinoFragment(string $nino): string
    {
        $nino = str_replace(" ", "", $nino);

        return substr($nino, strlen($nino) - 4, strlen($nino));
    }
}

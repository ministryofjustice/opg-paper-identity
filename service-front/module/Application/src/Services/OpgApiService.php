<?php

declare(strict_types=1);

namespace Application\Services;

use Application\Contracts\OpgApiServiceInterface;
use GuzzleHttp\Client;
use Laminas\Http\Response;
use Application\Exceptions\OpgApiException;

class OpgApiService implements OpgApiServiceInterface
{
    public function __construct(private Client $httpClient, private readonly array $config)
    {
    }

    public function getIdOptionsData(): array
    {
        $baseUrl = $this->config['base-url'];

        try {
            $response = $this->httpClient->get($baseUrl . '/identity/method');

            if ($response->getStatusCode() !== Response::STATUS_CODE_200) {
                throw new OpgApiException($response->getReasonPhrase());
            }
            return json_decode($response->getBody()->getContents(), true);
        } catch (\GuzzleHttp\Exception\BadResponseException $exception) {
            throw new OpgApiException($exception->getMessage());
        }
    }

    public function getDetailsData(): array
    {
        $baseUrl = $this->config['base-url'];

        try {
            $response = $this->httpClient->get($baseUrl . '/identity/details');

            if ($response->getStatusCode() !== Response::STATUS_CODE_200) {
                throw new OpgApiException($response->getReasonPhrase());
            }
            return json_decode($response->getBody()->getContents(), true);
        } catch (\GuzzleHttp\Exception\BadResponseException $exception) {
            throw new OpgApiException($exception->getMessage());
        }
    }
}

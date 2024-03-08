<?php

declare(strict_types=1);

namespace Application\Services;

use Application\Contracts\OpgApiServiceInterface;
use GuzzleHttp\Client;
use Laminas\Http\Response;
use Application\Exceptions\OpgApiException;

class OpgApiService implements OpgApiServiceInterface
{
    public function __construct(private Client $httpClient)
    {
    }

    public function makeApiRequest(string $uri): array
    {
        try {
            $response = $this->httpClient->get($uri);

            if ($response->getStatusCode() !== Response::STATUS_CODE_200) {
                throw new OpgApiException($response->getReasonPhrase());
            }
            return json_decode($response->getBody()->getContents(), true);
        } catch (\GuzzleHttp\Exception\BadResponseException $exception) {
            throw new OpgApiException($exception->getMessage());
        }
    }

    public function getIdOptionsData(): array
    {
        return $this->makeApiRequest('/identity/method');
    }

    public function getDetailsData(): array
    {
        return $this->makeApiRequest('/identity/details');
    }

    public function getAddressVerificationData(): array
    {
        return $this->makeApiRequest('/identity/address_verification');
    }

    public function getLpasByDonorData(): array
    {
        return $this->makeApiRequest('/identity/list_lpas');
    }
}

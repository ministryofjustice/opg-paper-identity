<?php

declare(strict_types=1);

namespace Application\Services;

use Application\Contracts\OpgApiServiceInterface;
use GuzzleHttp\Client;

class OpgApiService implements OpgApiServiceInterface
{
    function __construct(private Client $httpClient, private readonly array $config) {}

    public function getIdOptionsData()
    {
        $data = $this->httpClient->get($this->config['base-url'] . '/identity/method');

        return json_decode($data->getBody()->getContents(), true);
    }

    public function getDetailsData()
    {
        $data = $this->httpClient->get($this->config['base-url'] . '/identity/details');

        return json_decode($data->getBody()->getContents(), true);
    }
}
<?php

declare(strict_types=1);

namespace Application\DWP\DwpApi;

use Application\DWP\AuthApi\AuthApiException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

class DwpApiService
{
    function __construct(
        private Client $guzzleClientCitizen,
        private Client $guzzleClientMatch,
        private AuthApiService $authApiService,
        private LoggerInterface $logger,
        private array $config
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
}

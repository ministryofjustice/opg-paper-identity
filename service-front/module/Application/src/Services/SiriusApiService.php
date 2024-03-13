<?php

declare(strict_types=1);

namespace Application\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Laminas\Http\Header\Cookie;
use Laminas\Http\Request;
use Laminas\Stdlib\RequestInterface;

class SiriusApiService
{
    public function __construct(
        public readonly Client $client
    ) {
    }

    public function checkAuth(RequestInterface $request): bool
    {
        if (! ($request instanceof Request)) {
            return false;
        }

        $cookieHeader = $request->getHeader('Cookie');

        if (! ($cookieHeader instanceof Cookie)) {
            return false;
        }

        try {
            $this->client->get('/api/v1/users/current', [
                'headers' => [
                    'Cookie' => $cookieHeader->getFieldValue(),
                ],
            ]);
        } catch (GuzzleException $e) {
            return false;
        }

        return true;
    }
}

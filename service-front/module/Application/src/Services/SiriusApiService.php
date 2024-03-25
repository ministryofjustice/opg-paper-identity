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
        private readonly Client $client
    ) {
    }

    private function getAuthHeaders(RequestInterface $request): ?array
    {
        if (! ($request instanceof Request)) {
            return null;
        }

        $cookieHeader = $request->getHeader('Cookie');

        if (! ($cookieHeader instanceof Cookie)) {
            return null;
        }

        return [
            'Cookie' => $cookieHeader->getFieldValue(),
        ];
    }

    public function checkAuth(RequestInterface $request): bool
    {
        try {
            $headers = $this->getAuthHeaders($request);

            if ($headers === null) {
                return false;
            }

            $this->client->get('/api/v1/users/current', [
                'headers' => $headers,
            ]);
        } catch (GuzzleException $e) {
            return false;
        }

        return true;
    }

    /**
     * @return array{"opg.poas.lpastore": array<string, mixed>}
     */
    public function getLpaByUid(string $uid, Request $request): array
    {
        $response = $this->client->get('/api/v1/digital-lpas/' . $uid, [
            'headers' => $this->getAuthHeaders($request),
        ]);

        return json_decode(strval($response->getBody()), true);
    }
}

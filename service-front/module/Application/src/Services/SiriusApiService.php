<?php

declare(strict_types=1);

namespace Application\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Laminas\Http\Header\Cookie;
use Laminas\Http\Request;
use Laminas\Stdlib\RequestInterface;
use Application\Helpers\AddressProcessorHelper;

/**
 * @psalm-type Address = array{
 *  line1: string,
 *  line2?: string,
 *  line3?: string,
 *  town?: string,
 *  postcode?: string,
 *  country: string,
 * }
 *
 * @psalm-type Lpa = array{
 *  "opg.poas.sirius": array{
 *    donor: array{
 *      firstname: string,
 *      surname: string,
 *      dob: string,
 *      addressLine1: string,
 *      addressLine2?: string,
 *      addressLine3?: string,
 *      town?: string,
 *      postcode?: string,
 *      country: string,
 *    },
 *  },
 *  "opg.poas.lpastore": ?array{
 *    donor: array{
 *      firstNames: string,
 *      lastName: string,
 *      dateOfBirth: string,
 *      address: Address,
 *    },
 *    certificateProvider: array{
 *      firstNames: string,
 *      lastName: string,
 *      address: Address,
 *    },
 *  },
 * }
 */

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
     * @return Lpa
     */
    public function getLpaByUid(string $uid, Request $request): array
    {
        $authHeaders = $this->getAuthHeaders($request) ?? [];

        $response = $this->client->get('/api/v1/digital-lpas/' . $uid, [
            'headers' => $authHeaders
        ]);

        $responseArray = json_decode(strval($response->getBody()), true);

        $responseArray['opg.poas.lpastore']['certificateProvider']['address'] = (new AddressProcessorHelper())
            ->getAddress($responseArray['opg.poas.lpastore']['certificateProvider']['address']);

        return $responseArray;
    }

    /**
     * @return array{
     *  addressLine1: string,
     *  addressLine2: string,
     *  addressLine3: string,
     *  town: string,
     *  postcode: string,
     * }[]
     */
    public function searchAddressesByPostcode(string $postcode, Request $request): array
    {
        $response = $this->client->get('/api/v1/postcode-lookup?postcode=' . $postcode, [
            'headers' => $this->getAuthHeaders($request),
        ]);

        return json_decode(strval($response->getBody()), true);
    }

    public function abandonCase(array $data, Request $request): array
    {
        $response = $this->client->post('/api/v1/identity-check', [
            'headers' => $this->getAuthHeaders($request),
            'json' => $data
        ]);

        return [
            'status' => $response->getStatusCode(),
            'error' => json_decode(strval($response->getBody()), true)
        ];
    }
}

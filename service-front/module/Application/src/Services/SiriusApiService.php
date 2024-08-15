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

    /**
     * @param string $base64suffix
     * @param array $caseDetails
     * @param Request $request
     * @return array
     * @throws GuzzleException
     *
     * @psalm-suppress InvalidArrayOffset
     */
    public function sendPostOfficePDf(string $base64suffix, array $caseDetails, Request $request): array
    {
        $address = [
            $caseDetails["address"]["line1"],
            $caseDetails["address"]["line2"],
            $caseDetails["address"]["line3"] ?? "N/A",
            $caseDetails["address"]["town"],
            $caseDetails["address"]["country"],
            $caseDetails["address"]["postcode"]
        ];

        $data = [
            "type" => "Save",
            "systemType" => "DLP-ID-PO-D",
            "content" => "",
            "suffix" => $base64suffix,
            "correspondentName" => $caseDetails['firstName'] . ' ' . $caseDetails['lastName'],
            "correspondentAddress" => $address
        ];
        $lpa = $caseDetails['lpas'][0];

        $lpaDetails = $this->getLpaByUid($lpa, $request);
        $lpaId = $lpaDetails["opg.poas.sirius"]["id"];

        if (! $lpaId) {
            return [
                'status' => 400,
                'response' => 'LPA Id not found'
            ];
        }
        $response = $this->client->post('/api/v1/lpas/' . $lpaId . '/documents', [
            'json' => $data
        ]);

        return [
            'status' => $response->getStatusCode(),
            'response' => json_decode(strval($response->getBody()), true)
        ];
    }
}

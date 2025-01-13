<?php

declare(strict_types=1);

namespace Application\Services;

use Application\Exceptions\PostcodeInvalidException;
use Application\Helpers\AddressProcessorHelper;
use Application\Enums\SiriusDocument;
use Application\Validators\LpaUidValidator;
use Application\Exceptions\UidInvalidException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Laminas\Http\Header\Cookie;
use Laminas\Http\Request;
use Laminas\Stdlib\RequestInterface;
use GuzzleHttp\Exception\ClientException;
use Psr\Log\LoggerInterface;

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
 * @psalm-type Attorney = array{
 *   firstNames: string,
 *   lastName: string,
 *   dateOfBirth: string,
 * }
 *
 * @psalm-type LinkedLpa = array{
 *    uId: string,
 *    caseSubtype: string,
 *    createdDate: string,
 *    status: string,
 *  }
 *
 * @psalm-type Lpa = array{
 *  "opg.poas.sirius": array{
 *    uId?: string,
 *    id: int,
 *    caseSubtype: string,
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
 *    linkedDigitalLpas?: LinkedLpa[]
 *  },
 *  "opg.poas.lpastore": ?array{
 *    lpaType: string,
 *    donor: array{
 *      firstNames: string,
 *      lastName: string,
 *      dateOfBirth: string,
 *      address: Address,
 *    },
 *    certificateProvider: array{
 *      firstNames: string,
 *      lastName: string,
 *      dateOfBirth: string,
 *      address: Address,
 *    },
 *    attorneys: Attorney[],
 *  },
 * }
 */
class SiriusApiService
{
    public function __construct(
        private readonly Client $client,
        private readonly LoggerInterface $logger
    ) {
    }

    private function getAuthHeaders(RequestInterface $request): ?array
    {
        if (! ($request instanceof Request)) {
            return null;
        }

        $cookieHeader = $request->getHeader('Cookie');

        if (! ($cookieHeader instanceof Cookie) || ! isset($cookieHeader['XSRF-TOKEN'])) {
            return null;
        }

        return [
            'Cookie' => $cookieHeader->getFieldValue(),
            'X-XSRF-TOKEN' => $cookieHeader['XSRF-TOKEN'],
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
        $validator = new LpaUidValidator();
        if (! $validator->isValid($uid)) {
            throw new UidInvalidException(join(", ", $validator->getMessages()));
        }
        $authHeaders = $this->getAuthHeaders($request) ?? [];

        $response = $this->client->get('/api/v1/digital-lpas/' . urlencode($uid), [
            'headers' => $authHeaders
        ]);

        $responseArray = json_decode(strval($response->getBody()), true);

        if (isset($responseArray['opg.poas.lpastore']['certificateProvider']['address'])) {
            $responseArray['opg.poas.lpastore']['certificateProvider']['address'] = (new AddressProcessorHelper())
                ->getAddress($responseArray['opg.poas.lpastore']['certificateProvider']['address']);
        }

        return $responseArray;
    }

    /**
     * @return Lpa[]
     */
    public function getAllLinkedLpasByUid(string $uid, Request $request): array
    {
        $responseArray = [];
        try {
            $response = $this->getLpaByUid($uid, $request);
        } catch (ClientException $clientException) {
            $response = $clientException->getResponse();
            if ($response->getStatusCode() === 404) {
                $this->logger->info("uid: {$uid} not found");
                return $responseArray;
            } else {
                throw $clientException;
            }
        }

        $linkedUids = $response['opg.poas.sirius']['linkedDigitalLpas'] ?? [];
        $responseArray[$uid] = $response;
        if ($linkedUids === []) {
            return $responseArray;
        }

        foreach ($linkedUids as $lpaId) {
            try {
                $response = $this->getLpaByUid($lpaId["uId"], $request);
                $responseArray[$lpaId["uId"]] = $response;
            } catch (ClientException $clientException) {
                $response = $clientException->getResponse();
                if ($response->getStatusCode() === 404) {
                    $this->logger->info("uid: {$uid} not found");
                } else {
                    throw $clientException;
                }
            }
        }
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
        try {
            $response = $this->client->get('/api/v1/postcode-lookup?postcode=' . $postcode, [
                'headers' => $this->getAuthHeaders($request)
            ]);
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 400) {
                $errorMessage = sprintf('Bad Request error returned from postcode lookup: %s', $e->getMessage());
                throw new PostcodeInvalidException($errorMessage);
            }

            throw $e;
        }

        return json_decode(strval($response->getBody()), true);
    }

    /**
     * @param array{
     *   reference: string,
     *   actorType: string,
     *   lpaIds: string[],
     *   time: string,
     *   outcome: string,
     * } $data
     * @return array{status: int, error: mixed}
     */
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
     * @param array $caseDetails
     * @param SiriusDocument $systemType
     * @param Request $request
     * @param string $pdfSuffixBase64 Optional. PDF file in base-64 format, if provided
     *                                will be added to the generated letter.
     * @return array
     * @throws GuzzleException
     */
    public function sendDocument(
        array $caseDetails,
        SiriusDocument $systemType,
        Request $request,
        string $pdfSuffixBase64 = null
    ): array {
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
            "systemType" => $systemType,
            "content" => "",
            "correspondentName" => $caseDetails['firstName'] . ' ' . $caseDetails['lastName'],
            "correspondentAddress" => $address
        ];
        if ($pdfSuffixBase64 !== null) {
            $data["pdfSuffix"] = $pdfSuffixBase64;
        }
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
            'headers' => $this->getAuthHeaders($request),
            'json' => $data
        ]);

        return [
            'status' => $response->getStatusCode(),
            'response' => json_decode(strval($response->getBody()), true)
        ];
    }

    /**
    * @return array{handle: string, label: string}
    */
    public function getCountryList(Request $request): array
    {
        $response = $this->client->get(
            '/api/v1/reference-data/country',
            ['headers' => $this->getAuthHeaders($request)]
        );

        return json_decode(strval($response->getBody()), true);
    }
}

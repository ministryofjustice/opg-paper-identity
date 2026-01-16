<?php

declare(strict_types=1);

namespace Application\Services;

use Application\Auth\JwtGenerator;
use Application\Enums\SiriusDocument;
use Application\Exceptions\HttpException;
use Application\Exceptions\LpaNotFoundException;
use Application\Exceptions\PostcodeInvalidException;
use Application\Exceptions\UidInvalidException;
use Application\Helpers\AddressProcessorHelper;
use Application\Validators\LpaUidValidator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\RequestInterface;
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
 *    uId: string,
 *    id: int,
 *    caseSubtype: string,
 *    donor: array{
 *      id?: int,
 *      firstname: string,
 *      surname: string,
 *      dob: string,
 *      addressLine1: string,
 *      addressLine2?: string,
 *      addressLine3?: string,
 *      town?: string,
 *      postcode?: string,
 *      country: string
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
 *      identityCheck?: array{
 *        checkedAt: string,
 *        type: string
 *      },
 *    },
 *    certificateProvider: array{
 *      firstNames: string,
 *      lastName: string,
 *      dateOfBirth: string,
 *      address: Address,
 *      identityCheck?: array{
 *        checkedAt: string,
 *        type: string
 *      },
 *    },
 *    attorneys: Attorney[],
 *    status: string
 *  },
 * }
 */
class SiriusApiService
{
    public function __construct(
        private readonly Client $client,
        private readonly LoggerInterface $logger,
        private readonly JwtGenerator $jwtGenerator,
    ) {
    }

    private function getAuthHeaders(RequestInterface $request): ?array
    {
        $cookieHeader = $request->getHeaderLine('Cookie');

        $cookies = [];
        mb_parse_str(
            strtr($cookieHeader, ['&' => '%26', '+' => '%2B', ';' => '&']),
            $cookies
        );

        return [
            'Cookie' => $cookieHeader,
            'X-XSRF-TOKEN' => $cookies['XSRF-TOKEN'] ?? '',
        ];
    }

    public function checkAuth(RequestInterface $request): bool
    {
        try {
            $headers = $this->getAuthHeaders($request);

            $response = $this->client->get('/api/v1/users/current', [
                'headers' => $headers,
            ]);

            $user = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            $this->jwtGenerator->setSub($user['email']);
        } catch (GuzzleException $e) {
            return false;
        }

        return true;
    }

    /**
     * @return ?Lpa
     */
    public function getLpaByUid(string $uid, RequestInterface $request): ?array
    {
        $validator = new LpaUidValidator();
        if (! $validator->isValid($uid)) {
            throw new UidInvalidException(join(", ", $validator->getMessages()));
        }
        $authHeaders = $this->getAuthHeaders($request) ?? [];

        try {
            $response = $this->client->get('/api/v1/digital-lpas/' . urlencode($uid), [
                'headers' => $authHeaders,
            ]);
        } catch (ClientException $clientException) {
            $response = $clientException->getResponse();
            if ($response->getStatusCode() === 404) {
                $this->logger->info("uid: {$uid} not found");

                return null;
            } else {
                throw $clientException;
            }
        }

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
    public function getAllLinkedLpasByUid(string $uid, RequestInterface $request): array
    {
        $responseArray = [];

        $lpa = $this->getLpaByUid($uid, $request);

        if (empty($lpa)) {
            return $responseArray;
        }

        $responseArray[$uid] = $lpa;

        $linkedUids = $lpa['opg.poas.sirius']['linkedDigitalLpas'] ?? [];
        if ($linkedUids === []) {
            return $responseArray;
        }

        foreach ($linkedUids as $lpaId) {
            $lpa = $this->getLpaByUid($lpaId["uId"], $request);
            if (! empty($lpa)) {
                $responseArray[$lpaId["uId"]] = $lpa;
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
    public function searchAddressesByPostcode(string $postcode, RequestInterface $request): array
    {
        try {
            $response = $this->client->get('/api/v1/postcode-lookup?postcode=' . $postcode, [
                'headers' => $this->getAuthHeaders($request),
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
     * @param ?string $pdfSuffixBase64 Optional. PDF file in base-64 format, if provided
     *                                will be added to the generated letter.
     * @throws GuzzleException
     */
    public function sendDocument(
        array $caseDetails,
        SiriusDocument $systemType,
        RequestInterface $request,
        ?string $pdfSuffixBase64 = null
    ): array {
        $address = [
            $caseDetails["address"]["line1"],
            $caseDetails["address"]["line2"],
            $caseDetails["address"]["line3"] ?? "N/A",
            $caseDetails["address"]["town"],
            $caseDetails["address"]["country"],
            $caseDetails["address"]["postcode"],
        ];

        $data = [
            "type" => "Save",
            "systemType" => $systemType,
            "content" => "",
            "correspondentName" => $caseDetails['firstName'] . ' ' . $caseDetails['lastName'],
            "correspondentAddress" => $address,
        ];

        if ($systemType === SiriusDocument::PostOfficeDocCheckVoucher) {
            $data['donorFirstNames'] = $caseDetails['vouchingFor']['firstName'];
            $data['donorLastName'] = $caseDetails['vouchingFor']['lastName'];
        }

        if ($pdfSuffixBase64 !== null) {
            $data["pdfSuffix"] = $pdfSuffixBase64;
        }
        $lpa = $caseDetails['lpas'][0];

        $lpaDetails = $this->getLpaByUid($lpa, $request);
        if (is_null($lpaDetails)) {
            throw new LpaNotFoundException("LPA not found: {$lpa}");
        }
        $lpaId = $lpaDetails["opg.poas.sirius"]["id"];

        if (! $lpaId) {
            return [
                'status' => 400,
                'response' => 'LPA Id not found',
            ];
        }
        $response = $this->client->post('/api/v1/lpas/' . $lpaId . '/documents', [
            'headers' => $this->getAuthHeaders($request),
            'json' => $data,
        ]);

        return [
            'status' => $response->getStatusCode(),
            'response' => json_decode(strval($response->getBody()), true),
        ];
    }

    public function addNote(
        RequestInterface $request,
        string $uid,
        string $name,
        string $type,
        string $description
    ): void {
        $lpaDetails = $this->getLpaByUid($uid, $request);
        if (is_null($lpaDetails)) {
            throw new LpaNotFoundException("LPA not found: {$uid}");
        }
        $caseId = $lpaDetails["opg.poas.sirius"]["id"] ?? null;
        $donorId = $lpaDetails["opg.poas.sirius"]["donor"]["id"] ?? null;

        if ($caseId === null) {
            throw new HttpException(400, 'Case Id not found');
        }

        if ($donorId === null) {
            throw new HttpException(400, 'Donor Id not found');
        }

        $data = [
            "ownerId" => $caseId,
            "ownerType" => "case",
            "name" => $name,
            "type" => $type,
            "description" => $description,
        ];

        $this->client->post('/api/v1/persons/' . $donorId . '/notes', [
            'headers' => $this->getAuthHeaders($request),
            'json' => $data,
        ]);
    }

    /**
     * @return array{handle: string, label: string}
     */
    public function getCountryList(RequestInterface $request): array
    {
        $response = $this->client->get(
            '/api/v1/reference-data/country',
            ['headers' => $this->getAuthHeaders($request)]
        );

        return json_decode(strval($response->getBody()), true);
    }
}

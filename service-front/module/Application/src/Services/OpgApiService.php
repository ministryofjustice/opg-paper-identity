<?php

declare(strict_types=1);

namespace Application\Services;

use Application\Contracts\OpgApiServiceInterface;
use GuzzleHttp\Client;
use Laminas\Http\Response;
use Application\Exceptions\OpgApiException;
use Monolog\Logger;

class OpgApiService implements OpgApiServiceInterface
{
    /**
     * @psalm-suppress PropertyNotSetInConstructor
     * @psalm-suppress PossiblyUnusedProperty
     */
    protected int $responseStatus;
    /**
     * @psalm-suppress PropertyNotSetInConstructor
     * @psalm-suppress PossiblyUnusedProperty
     */
    protected array $responseData;

    public function __construct(private Client $httpClient)
    {
    }

    public function stubDetailsResponse(): array
    {
        /**
         * This is a temporary function to prevent the start page crashing with a 500 error
         * now that the equivalent API function requires a UUID
         */
        return [
            "FirstName" => "Mary Anne",
            "LastName" => "Chapman",
            "DOB" => "01 May 1943",
            "Address" => "1 Court Street, London, UK, SW1B 1BB",
            "Role" => "Donor",
            "LPA" => [
                "PA M-XYXY-YAGA-35G3",
                "PW M-XYXY-YAGA-35G4"
            ]
        ];
    }

    public function makeApiRequest(string $uri, string $verb = 'get', array $data = [], array $headers = []): array
    {
        try {
            $response = $this->httpClient->request($verb, $uri, [
                'headers' => $headers,
                'json' => $data,
                'debug' => true
            ]);

            $this->responseStatus = Response::STATUS_CODE_200;
            $this->responseData = json_decode($response->getBody()->getContents(), true);

            if ($response->getStatusCode() !== Response::STATUS_CODE_200) {
                throw new OpgApiException($response->getReasonPhrase());
            }
            return $this->responseData;
        } catch (\GuzzleHttp\Exception\BadResponseException $exception) {
            throw new OpgApiException($exception->getMessage());
        }
    }

    public function getDetailsData(string $uuid): array
    {
        return $this->makeApiRequest('/identity/details?uuid=' . $uuid);
    }

    public function getAddressVerificationData(): array
    {
        return $this->makeApiRequest('/identity/address_verification');
    }

    public function getLpasByDonorData(): array
    {
        return $this->makeApiRequest('/identity/list_lpas');
    }

    public function checkNinoValidity(string $nino): string
    {
        $nino = strtoupper(preg_replace('/(\s+)|(-)/', '', $nino));

        try {
            $this->makeApiRequest(
                '/identity/validate_nino',
                'POST',
                ['nino' => $nino],
                ['Content-Type' => 'application/json']
            );
        } catch (OpgApiException $opgApiException) {
            return $opgApiException->getMessage();
        }

        return $this->responseData['status'];
    }

    public function checkDlnValidity(string $dln): string
    {
        $dln = strtoupper(preg_replace('/(\s+)|(-)/', '', $dln));

        try {
            $this->makeApiRequest(
                '/identity/validate_driving_licence',
                'POST',
                ['dln' => $dln],
                ['Content-Type' => 'application/json']
            );
        } catch (OpgApiException $opgApiException) {
            return $opgApiException->getMessage();
        }

        return $this->responseData['status'];
    }

    public function checkPassportValidity(string $passport): string
    {
        $passport = strtoupper(preg_replace('/(\s+)|(-)/', '', $passport));

        try {
            $this->makeApiRequest(
                '/identity/validate_passport',
                'POST',
                ['passport' => $passport],
                ['Content-Type' => 'application/json']
            );
        } catch (OpgApiException $opgApiException) {
            return $opgApiException->getMessage();
        }

        return $this->responseData['status'];
    }

    public function getIdCheckQuestions(string $uuid): array|bool
    {
        try {
            return $this->makeApiRequest("/cases/$uuid/kbv-questions");
        } catch (OpgApiException $opgApiException) {
            return false;
        }
    }

    public function checkIdCheckAnswers(string $uuid, array $answers): bool
    {
        try {
            $response = $this->makeApiRequest("/cases/$uuid/kbv-answers", 'POST', $answers);
            if ($response['result'] !== 'pass') {
                return false;
            }
            return true;
        } catch (OpgApiException $opgApiException) {
            return false;
        }
    }

    public function createCase(
        string $firstname,
        string $lastname,
        string $dob,
        string $personType,
        array $lpas,
        array $address,
    ): array {
        return $this->makeApiRequest("/cases/create", 'POST', [
            'firstName' => $firstname,
            'lastName' => $lastname,
            'dob' => $dob,
            'personType' => $personType,
            'lpas' => $lpas,
            'address' => $address
        ]);
    }

    public function updateIdMethod(string $uuid, string $method): string|bool
    {
        $data = [
            'idMethod' => $method
        ];
        try {
            $response = $this->makeApiRequest("/cases/$uuid/update-method", 'POST', $data);
            return $response['result'];
        } catch (OpgApiException $opgApiException) {
            return false;
        }
    }

    public function listPostOfficesByPostcode(string $uuid, string $postcode): array|bool
    {
//        $data = [
//            'postcode' => $postcode
//        ];
//        try {
//            $response = $this->makeApiRequest("/cases/$uuid/find-post-office", 'POST', $data);
//            return $response['result'];
//        } catch (OpgApiException $opgApiException) {
//            return false;
//        }

        return [
            'hednesford' => [
                'name' => 'Hednesford',
                'address' => '45 Market Street, Hednesford, Cannock, WS12 1AY',
                'distance' => '0.5 miles'
            ],
            'chadsmoor' => [
                'name' => 'Chadsmoor',
                'address' => '207-209 Cannock Road, Chadsmoor, Cannock, WS11 5DD',
                'distance' => '1.1 miles'
            ],
            'hazelslade' => [
                'name' => 'Hazelslade',
                'address' => '71 Rugeley Road, Hazelslade, Cannock, WS12 0PQ',
                'distance' => '1.2 miles'
            ],
            'wimblebury' => [
                'name' => 'Wimblebury',
                'address' => '66-68 John Street, Wimblebury, Cannock, WS12 2RJ',
                'distance' => '1.4 miles'
            ],
            'heath_hayes' =>  [
                'name' => 'Heath Hayes',
                'address' => '151 Hednesford Road, Heath Hayes, Cannock, WS12 3HN',
                'distance' => '1.9 miles'
            ]
        ];
    }

}

<?php

declare(strict_types=1);

namespace Application\Services;

use Application\Contracts\OpgApiServiceInterface;
use Application\Exceptions\HttpException;
use Application\Helpers\AddressProcessorHelper;
use GuzzleHttp\Client;
use Laminas\Http\Response;
use Application\Exceptions\OpgApiException;
use GuzzleHttp\Exception\BadResponseException;
use Throwable;

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

    public function makeApiRequest(string $uri, string $verb = 'get', array $data = [], array $headers = []): array
    {
        try {
            $response = $this->httpClient->request($verb, $uri, [
                'headers' => $headers,
                'json' => $data
            ]);

            $this->responseStatus = Response::STATUS_CODE_200;
            $this->responseData = json_decode($response->getBody()->getContents(), true);

            if ($response->getStatusCode() !== Response::STATUS_CODE_200) {
                throw new OpgApiException($response->getReasonPhrase());
            }
            return $this->responseData;
        } catch (\GuzzleHttp\Exception\BadResponseException $exception) {
            throw new OpgApiException($exception->getMessage(), 0, $exception);
        }
    }

    public function getDetailsData(string $uuid): array
    {
        try {
            $response = $this->makeApiRequest('/identity/details?uuid=' . $uuid);
            $response['address'] = (new AddressProcessorHelper())->getAddress($response['address']);
            if (
                array_key_exists('alternateAddress', $response) &&
                ! empty($response['alternateAddress'])
            ) {
                $response['alternateAddress'] = (
                    new AddressProcessorHelper()
                )->getAddress($response['alternateAddress']);
            }

            return $response;
        } catch (OpgApiException $exception) {
            $previous = $exception->getPrevious();
            if ($previous instanceof BadResponseException && $previous->getResponse()->getStatusCode() === 404) {
                throw new HttpException(404);
            }

            throw $exception;
        }
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
        string|null $dob,
        string $personType,
        array $lpas,
        array $address,
    ): array {

        $data = [
            'firstName' => $firstname,
            'lastName' => $lastname,
            'dob' => $dob,
            'personType' => $personType,
            'lpas' => $lpas,
            'address' => $address
        ];
        return $this->makeApiRequest("/cases/create", 'POST', $data);
    }

    public function findLpa(string $uuid, string $lpa): array
    {
        $uri = sprintf('cases/%s/find-lpa/%s', $uuid, strtoupper($lpa));

        try {
            $this->makeApiRequest(
                $uri,
                'GET',
                [],
                ['Content-Type' => 'application/json']
            );
        } catch (OpgApiException $opgApiException) {
            return [$opgApiException->getMessage()];
        }

        return $this->responseData;
    }

    public function updateIdMethod(string $uuid, string $method): array
    {
        $data = [
            'idMethod' => $method
        ];
        try {
            $this->makeApiRequest("/cases/$uuid/update-method", 'POST', $data);
        } catch (\Exception $exception) {
            throw new OpgApiException($exception->getMessage());
        }
        return $this->responseData;
    }

    public function updateCaseWithLpa(string $uuid, string $lpa, bool $remove = false): array
    {
        $verb = $remove ? 'DELETE' : 'PUT';
        $url = sprintf("/cases/%s/lpas/%s", $uuid, $lpa);

        try {
            $this->makeApiRequest($url, $verb);
        } catch (\Exception $exception) {
            throw new OpgApiException($exception->getMessage());
        }
        return $this->responseData;
    }

    public function listPostOfficesByPostcode(string $uuid, string $postcode): array
    {
//        $data = [
//            'postcode' => $postcode
//        ];
//        try {
//            $response = $this->makeApiRequest("/cases/$uuid/find-post-office", 'POST', $data);
//            return $response['result'];
//        } catch (OpgApiException $opgApiException) {
//            throw new OpgApiException('Post office reference code not found');
//        }

        // NEED TO INCLUDE PAGINATION LOGIC HERE AS WELL

        return [
            12345672 => [
                'name' => 'Hednesford',
                'address' => '45 Market Street, Hednesford, Cannock, WS12 1AY',
            ],
            12345673 => [
                'name' => 'Chadsmoor',
                'address' => '207-209 Cannock Road, Chadsmoor, Cannock, WS11 5DD',
            ],
            12345674 => [
                'name' => 'Hazelslade',
                'address' => '71 Rugeley Road, Hazelslade, Cannock, WS12 0PQ',
            ],
            12345675 => [
                'name' => 'Wimblebury',
                'address' => '66-68 John Street, Wimblebury, Cannock, WS12 2RJ',
            ],
            12345676 => [
                'name' => 'Heath Hayes',
                'address' => '151 Hednesford Road, Heath Hayes, Cannock, WS12 3HN',
            ]
        ];
    }

    public function searchPostOfficesByLocation(
        string $uuid,
        string $location,
        int $page = 1
    ): array {
//        $data = [
//            'postcode' => $postcode
//        ];
//        try {
//            $response = $this->makeApiRequest("/cases/$uuid/search-post-office-by-location", 'POST', $data);
//            return $response['result'];
//        } catch (OpgApiException $opgApiException) {
//            throw new OpgApiException('Post office reference code not found');
//        }

        return [
            12345672 => [
                'name' => 'Hednesford',
                'address' => '45 Market Street, Hednesford, Cannock, WS12 1AY',
            ],
            12345673 => [
                'name' => 'Chadsmoor',
                'address' => '207-209 Cannock Road, Chadsmoor, Cannock, WS11 5DD',
            ],
            12345674 => [
                'name' => 'Hazelslade',
                'address' => '71 Rugeley Road, Hazelslade, Cannock, WS12 0PQ',
            ],
            12345675 => [
                'name' => 'Wimblebury',
                'address' => '66-68 John Street, Wimblebury, Cannock, WS12 2RJ',
            ],
            12345676 => [
                'name' => 'Heath Hayes',
                'address' => '151 Hednesford Road, Heath Hayes, Cannock, WS12 3HN',
            ]
        ];
    }

    public function getPostOfficeByCode(string $uuid, int $code): array
    {
//        $data = [
//            'postcode' => $postcode
//        ];
//        try {
//            $response = $this->makeApiRequest("/cases/$uuid/find-post-office", 'POST', $data);
//            return $response['result'];
//        } catch (OpgApiException $opgApiException) {
//            throw new OpgApiException('Post office reference code not found');
//        }

        $postOffices = [
            12345672 => [
                'name' => 'Hednesford',
                'address' => '45 Market Street, Hednesford, Cannock, WS12 1AY',
            ],
            12345673 => [
                'name' => 'Chadsmoor',
                'address' => '207-209 Cannock Road, Chadsmoor, Cannock, WS11 5DD',
            ],
            12345674 => [
                'name' => 'Hazelslade',
                'address' => '71 Rugeley Road, Hazelslade, Cannock, WS12 0PQ',
            ],
            12345675 => [
                'name' => 'Wimblebury',
                'address' => '66-68 John Street, Wimblebury, Cannock, WS12 2RJ',
            ],
            12345676 => [
                'name' => 'Heath Hayes',
                'address' => '151 Hednesford Road, Heath Hayes, Cannock, WS12 3HN',
            ]
        ];
        if (array_key_exists($code, $postOffices)) {
            return $postOffices[$code] ;
        } else {
            throw new OpgApiException('Post office reference code not found');
        }
    }

    public function addSearchPostcode(string $uuid, string $postcode): array
    {
        $data = [
            'selected_postcode' => $postcode
        ];
        try {
            $this->makeApiRequest("/cases/$uuid/add-search-postcode", 'POST', $data);
        } catch (\Exception $exception) {
            throw new OpgApiException($exception->getMessage());
        }
        return $this->responseData;
    }

    public function addSelectedPostOffice(string $uuid, string $postOffice): array
    {
        $data = [
            'selected_postoffice' => $postOffice
        ];
        try {
            $this->makeApiRequest("/cases/$uuid/add-selected-postoffice", 'POST', $data);
        } catch (\Exception $exception) {
            throw new OpgApiException($exception->getMessage());
        }
        return $this->responseData;
    }


    public function confirmSelectedPostOffice(string $uuid, string $deadline): array
    {
        $data = [
            'deadline' => $deadline
        ];
        try {
            $this->makeApiRequest("/cases/$uuid/confirm-selected-postoffice", 'POST', $data);
        } catch (\Exception $exception) {
            throw new OpgApiException($exception->getMessage());
        }
        return $this->responseData;
    }

    public function addSelectedAltAddress(string $uuid, array $data): array
    {
        $url = sprintf("/cases/%s/save-alternate-address-to-case", $uuid);

        try {
            $this->makeApiRequest($url, 'POST', $data);
        } catch (\Exception $exception) {
            throw new OpgApiException($exception->getMessage());
        }
        return $this->responseData;
    }

    public function updateCaseSetDocumentComplete(string $uuid): array
    {
        $url = sprintf("/cases/%s/complete-document", $uuid);

        try {
            $this->makeApiRequest($url, 'POST');
        } catch (\Exception $exception) {
            throw new OpgApiException($exception->getMessage());
        }
        return $this->responseData;
    }
}

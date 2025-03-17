<?php

declare(strict_types=1);

namespace Application\Services;

use Application\Auth\JwtGenerator;
use Application\Contracts\OpgApiServiceInterface;
use Application\Exceptions\HttpException;
use Application\Exceptions\OpgApiException;
use Application\Helpers\AddressProcessorHelper;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Laminas\Http\Response;

/**
 * @psalm-import-type CaseData from OpgApiServiceInterface
 * @psalm-import-type Question from OpgApiServiceInterface
 */
class OpgApiService implements OpgApiServiceInterface
{
    public const TYPE_HEADER = 'application/json';
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

    public function __construct(
        private Client $httpClient,
        private JwtGenerator $jwtGenerator,
    ) {
    }

    public function healthCheck(): bool
    {
        try {
            $this->makeApiRequest(
                '/health-check',
                'GET',
                ['Content-Type' => self::TYPE_HEADER]
            );

            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }

    private function makeApiRequest(string $uri, string $verb = 'get', array $data = [], array $headers = []): array
    {
        $headers['Authorization'] = 'Bearer ' . $this->jwtGenerator->issueToken();

        try {
            $response = $this->httpClient->request($verb, $uri, [
                'headers' => $headers,
                'json' => $data,
            ]);

            $this->responseStatus = Response::STATUS_CODE_200;
            $this->responseData = json_decode($response->getBody()->getContents(), true) ?? [];

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

            $response['firstName'] = $response['claimedIdentity']['firstName'];
            $response['lastName'] = $response['claimedIdentity']['lastName'];
            $response['address'] = $response['claimedIdentity']['address'];
            $response['professionalAddress'] = $response['claimedIdentity']['professionalAddress'] ?? null;
            $response['dob'] = $response['claimedIdentity']['dob'];

            unset($response['claimedIdentity']);

            if ($response['address']) {
                $response['address'] = (new AddressProcessorHelper())->getAddress($response['address']);
            }
            if (
                array_key_exists('professionalAddress', $response) &&
                isset($response['professionalAddress'])
            ) {
                $response['professionalAddress'] = (
                    new AddressProcessorHelper()
                )->getAddress($response['professionalAddress']);
            }

            /** @var CaseData $response */
            return $response;
        } catch (OpgApiException $exception) {
            $previous = $exception->getPrevious();
            if ($previous instanceof BadResponseException && $previous->getResponse()->getStatusCode() === 404) {
                throw new HttpException(404);
            }

            throw $exception;
        }
    }

    public function checkNinoValidity(string $uuid, string $nino): string
    {
        $nino = strtoupper(preg_replace('/(\s+)|(-)/', '', $nino) ?? '');

        try {
            $this->makeApiRequest(
                sprintf('/identity/%s/validate_nino', $uuid),
                'POST',
                ['nino' => $nino],
                ['Content-Type' => self::TYPE_HEADER]
            );
        } catch (OpgApiException $opgApiException) {
            return $opgApiException->getMessage();
        }

        return $this->responseData['result'];
    }

    public function checkDlnValidity(string $dln): string
    {
        $dln = strtoupper(preg_replace('/(\s+)|(-)/', '', $dln) ?? '');

        try {
            $this->makeApiRequest(
                '/identity/validate_driving_licence',
                'POST',
                ['dln' => $dln],
                ['Content-Type' => self::TYPE_HEADER]
            );
        } catch (OpgApiException $opgApiException) {
            return $opgApiException->getMessage();
        }

        return $this->responseData['status'];
    }

    public function checkPassportValidity(string $passport): string
    {
        $passport = strtoupper(preg_replace('/(\s+)|(-)/', '', $passport) ?? '');

        try {
            $this->makeApiRequest(
                '/identity/validate_passport',
                'POST',
                ['passport' => $passport],
                ['Content-Type' => self::TYPE_HEADER]
            );
        } catch (OpgApiException $opgApiException) {
            return $opgApiException->getMessage();
        }

        return $this->responseData['status'];
    }

    public function getIdCheckQuestions(string $uuid): array|false
    {
        try {
            return $this->makeApiRequest("/cases/$uuid/kbv-questions");
        } catch (OpgApiException $opgApiException) {
            return false;
        }
    }

    public function checkIdCheckAnswers(string $uuid, array $answers): array
    {
        return $this->makeApiRequest("/cases/$uuid/kbv-answers", 'POST', $answers);
    }

    public function createCase(
        string $firstname,
        string $lastname,
        string|null $dob,
        string $personType,
        array $lpas,
        array $address,
    ): array {

        if ($personType == 'voucher') {
            $data = [
                'personType' => $personType,
                'lpas' => $lpas,
                'claimedIdentity' => [],
                'vouchingFor' => [
                    'firstName' => $firstname,
                    'lastName' => $lastname,
                ],
            ];
        } else {
            $data = [
                'claimedIdentity' => [
                    'firstName' => $firstname,
                    'lastName' => $lastname,
                    'dob' => $dob,
                    'address' => $address,
                ],
                'personType' => $personType,
                'lpas' => $lpas,
            ];
        }

        return $this->makeApiRequest("/cases/create", 'POST', $data);
    }

    public function updateCaseAddress(string $uuid, array $address): void
    {
        $this->makeApiRequest("/cases/update/$uuid", 'PATCH', [
            'claimedIdentity' => [
                'address' => $address,
            ],
        ]);
    }

    public function updateIdMethod(string $uuid, string $method): void
    {
        $data = [
            'idMethod' => $method,
        ];

        try {
            $this->makeApiRequest("/cases/$uuid/update-method", 'POST', $data);
        } catch (\Exception $exception) {
            throw new OpgApiException($exception->getMessage());
        }
    }

    public function updateCaseWithLpa(string $uuid, string $lpa, bool $remove = false): void
    {
        $verb = $remove ? 'DELETE' : 'PUT';
        $url = sprintf("/cases/%s/lpas/%s", $uuid, $lpa);

        try {
            $this->makeApiRequest($url, $verb);
        } catch (\Exception $exception) {
            throw new OpgApiException($exception->getMessage());
        }
    }

    public function listPostOfficesByPostcode(string $uuid, string $location): array
    {
        $data = [
            'search_string' => $location,
        ];

        try {
            $this->makeApiRequest("/counter-service/branches", 'POST', $data);
        } catch (OpgApiException $opgApiException) {
            throw new OpgApiException($opgApiException->getMessage());
        }

        return $this->responseData;
    }

    public function addSelectedPostOffice(string $uuid, string $postOffice): void
    {
        $data = [
            'selected_postoffice' => $postOffice,
        ];

        try {
            $this->makeApiRequest("/cases/$uuid/add-selected-postoffice", 'POST', $data);
        } catch (\Exception $exception) {
            throw new OpgApiException($exception->getMessage());
        }
    }

    public function addSelectedAddress(string $uuid, array $data): void
    {
        $url = sprintf("/cases/%s/save-address-to-case", $uuid);

        try {
            $this->makeApiRequest($url, 'POST', $data);
        } catch (\Exception $exception) {
            throw new OpgApiException($exception->getMessage());
        }
    }

    public function updateCaseProfessionalAddress(string $uuid, array $data): void
    {
        $url = sprintf("/cases/%s/update-professional-address", $uuid);

        try {
            $this->makeApiRequest($url, 'POST', $data);
        } catch (\Exception $exception) {
            throw new OpgApiException($exception->getMessage());
        }
    }

    public function updateCaseSetDocumentComplete(string $uuid, string $idDocument, bool $state = true): void
    {
        $url = sprintf("/cases/%s/complete-document", $uuid);

        $data = [
            'idDocument' => $idDocument,
            'state' => $state,
        ];

        try {
            $this->makeApiRequest($url, 'POST', $data);
        } catch (\Exception $exception) {
            throw new OpgApiException($exception->getMessage());
        }
    }

    public function updateCaseSetDob(string $uuid, string $dob): void
    {
        $url = sprintf("/cases/%s/update-dob/%s", $uuid, $dob);

        try {
            $this->makeApiRequest($url, 'PUT');
        } catch (\Exception $exception) {
            throw new OpgApiException($exception->getMessage());
        }
    }

    public function updateCaseSetName(string $uuid, string $firstName, string $lastName): void
    {
        $url = sprintf("/cases/%s/update-name?firstName=%s&lastName=%s", $uuid, $firstName, $lastName);

        try {
            $this->makeApiRequest($url, 'PUT');
        } catch (\Exception $exception) {
            throw new OpgApiException($exception->getMessage());
        }
    }

    /**
     * @throws OpgApiException
     */
    public function updateIdMethodWithCountry(string $uuid, array $data): void
    {
        $url = sprintf("/cases/%s/update-cp-po-id", $uuid);

        $response = $this->makeApiRequest('/identity/details?uuid=' . $uuid);
        $methodData = [];

        if (array_key_exists('idMethodIncludingNation', $response)) {
            $methodData = $response['idMethodIncludingNation'];
        }

        foreach ($data as $key => $value) {
            $methodData[$key] = $value;
        }

        try {
            $this->makeApiRequest($url, 'PUT', $methodData);
        } catch (\Exception $exception) {
            throw new OpgApiException($exception->getMessage());
        }
    }

    public function updateCaseProgress(string $uuid, array $data): void
    {
        $url = sprintf("/cases/%s/save-case-progress", $uuid);

        try {
            $this->makeApiRequest($url, 'PUT', $data);
        } catch (\Exception $exception) {
            throw new OpgApiException($exception->getMessage());
        }
    }

    public function createYotiSession(string $uuid): array
    {
        $url = sprintf("/counter-service/%s/create-session", $uuid);

        try {
            $this->makeApiRequest($url, 'POST');
        } catch (\Exception $exception) {
            throw new OpgApiException($exception->getMessage());
        }

        return $this->responseData;
    }

    public function estimatePostofficeDeadline(string $uuid): string
    {
        $url = sprintf("/counter-service/%s/estimate-postoffice-deadline", $uuid);

        try {
            $this->makeApiRequest($url, 'GET');
        } catch (\Exception $exception) {
            throw new OpgApiException($exception->getMessage());
        }

        return $this->responseData['deadline'];
    }

    public function getServiceAvailability(string $uuid = null): array
    {
        $url = is_null($uuid) ? "/service-availability" : "/service-availability?uuid=$uuid";

        try {
            $this->makeApiRequest($url, 'GET');
        } catch (\Exception $exception) {
            throw new OpgApiException($exception->getMessage());
        }

        if (empty($this->responseData)) {
            throw new OpgApiException('Service availability data missing!');
        }

        return $this->responseData;
    }

    public function requestFraudCheck(string $uuid): array
    {
        $url = sprintf("/cases/%s/request-fraud-check", $uuid);

        try {
            $this->makeApiRequest($url, 'GET');
        } catch (\Exception $exception) {
            throw new OpgApiException($exception->getMessage());
        }

        if (empty($this->responseData)) {
            throw new OpgApiException('Unknown response received from fraud check service.');
        }

        return $this->responseData;
    }

    public function updateCaseAssistance(string $uuid, string $assistance, string $details = null): void
    {
        $url = sprintf("/cases/%s/save-case-assistance", $uuid);

        $data = [
            'assistance' => $assistance === 'yes' ? true : false,
            'details' => $details,
        ];

        try {
            $this->makeApiRequest($url, 'PUT', $data);
        } catch (\Exception $exception) {
            throw new OpgApiException($exception->getMessage());
        }
    }

    public function sendIdentityCheck(string $uuid): void
    {
        try {
            $this->makeApiRequest("/cases/{$uuid}/send-identity-check", 'POST');
        } catch (\Exception $exception) {
            throw new OpgApiException($exception->getMessage());
        }
    }
}

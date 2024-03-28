<?php

declare(strict_types=1);

namespace Application\Services;

use Application\Contracts\OpgApiServiceInterface;
use GuzzleHttp\Client;
use Laminas\Http\Response;
use Application\Exceptions\OpgApiException;

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
                'form_params' => $data,
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

    public function getIdOptionsData(): array
    {
        return $this->makeApiRequest('/identity/method');
    }

    public function getDetailsData(): array
    {
        return $this->makeApiRequest('/identity/details');
    }

    public function getAddressVerificationData(): array
    {
        return $this->makeApiRequest('/identity/address_verification');
    }

    public function getLpasByDonorData(): array
    {
        return $this->makeApiRequest('/identity/list_lpas');
    }

    public function checkNinoValidity(string $nino): bool
    {
        $nino = strtoupper(preg_replace('/(\s+)|(-)/', '', $nino));

        try {
            $this->makeApiRequest(
                '/identity/validate_nino',
                'POST',
                ['nino' => $nino],
                ['Content-Type' => 'application/x-www-form-urlencoded']
            );
        } catch (OpgApiException $opgApiException) {
            return false;
        }

        return $this->responseData['status'] === 'NINO check complete';
    }

    public function checkDlnValidity(string $dln): bool
    {
        $dln = strtoupper(preg_replace('/(\s+)|(-)/', '', $dln));

        try {
            $this->makeApiRequest(
                '/identity/validate_driving_licence',
                'POST',
                ['dln' => $dln],
                ['Content-Type' => 'application/x-www-form-urlencoded']
            );
        } catch (OpgApiException $opgApiException) {
            return false;
        }

        return $this->responseData['status'] === 'valid';
    }

    public function checkPassportValidity(string $passport): bool
    {
        $passport = strtoupper(preg_replace('/(\s+)|(-)/', '', $passport));

        try {
            $this->makeApiRequest(
                '/identity/validate_passport',
                'POST',
                ['passport' => $passport],
                ['Content-Type' => 'application/x-www-form-urlencoded']
            );
        } catch (OpgApiException $opgApiException) {
            return false;
        }

        return $this->responseData['status'] === 'valid';
    }

    public function getIdCheckQuestions(string $uuid): array
    {
        return $this->makeApiRequest("/cases/$uuid/kbv-questions");
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
}

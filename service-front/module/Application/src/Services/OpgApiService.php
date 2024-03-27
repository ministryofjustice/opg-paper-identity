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

    public function getIdCheckQuestions(string $case): array
    {
//        return $this->makeApiRequest("/cases/$case/kbv-questions");

        return [
            "one" => [
                "id" => 1,
                "question" => "Who provides your mortgage?",
                "number" => "one",
                "prompts" => [
                    0 => "Nationwide",
                    1 => "Halifax",
                    2 => "Lloyds",
                    3 => "HSBC",
                ]
            ],
            "two" => [
                "id" => 2,
                "question" => "Who provides your personal mobile contract?",
                "number" => "two",
                "prompts" => [
                    0 => "EE",
                    1 => "Vodafone",
                    2 => "BT",
                    3 => "iMobile",
                ]
            ],
            "three" => [
                "id" => 3,
                "question" => "What are the first two letters of the last name of another person on the electroal register at your address?",
                "number" => "three",
                "prompts" => [
                    0 => "Ka",
                    1 => "Ch",
                    2 => "Jo",
                    3 => "None of the above",
                ]
            ],
            "four" => [
                "id" => 4,
                "question" => "Who provides your current account?",
                "number" => "four",
                "prompts" => [
                    0 => "Santander",
                    1 => "HSBC",
                    2 => "Halifax",
                    3 => "Nationwide",
                ]
            ]
        ];
    }

    public function checkIdCheckAnswers(array $answers): bool
    {
        $answers = [];
        $data = $this->getRequest()->getPost();
        $result = 'pass';

        $answers['uid'] = [
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
        ];


        foreach ($answers as $key => $value) {
            if ($value != $answers['uid'][$key]) {
                $result = 'fail';
            }
        }

        return $result === 'pass';
    }
}

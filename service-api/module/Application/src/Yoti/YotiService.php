<?php

declare(strict_types=1);

namespace Application\Yoti;

use Application\Aws\Secrets\AwsSecret;
use Application\Exceptions\YotiException;
use Application\Model\Entity\CaseData;
use Application\Yoti\Http\Exception\YotiApiException;
use Application\Yoti\Http\RequestSigner;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Laminas\Http\Response;
use Psr\Log\LoggerInterface;
use DateTime;
use Ramsey\Uuid\Uuid;
use GuzzleHttp\Exception\ClientException;

/**
 * @psalm-suppress PossiblyUnusedProperty
 * Suppress unused $client pending implementation
 */
class YotiService implements YotiServiceInterface
{
    public function __construct(
        public readonly Client $client,
        private readonly LoggerInterface $logger,
        private readonly AwsSecret $sdkId,
        private readonly AwsSecret $key
    ) {
    }

    /**
     * @param string $postCode
     * @return array{
     *      branches: array{
     *          type: string,
     *          fad_code: string,
     *          name: string,
     *          address: string,
     *          postcode: string,
     *          location: array{
     *              latitude: float,
     *              longitude: float
     *          }
     *      }[]
     *  }
     * Get post offices near the location
     * @throws YotiException
     */
    public function postOfficeBranch(string $postCode): array
    {
        try {
            $results = $this->client->post('/idverify/v1/lookup/uk-post-office', [
                'json' => ['search_string' => $postCode],
            ]);
            if ($results->getStatusCode() !== Response::STATUS_CODE_200) {
                $this->logger->error('Post Office Lookup unsuccessful ', [
                    'data' => [ 'Post Code' => $postCode]
                ]);
                throw new YotiException($results->getReasonPhrase());
            }
            return json_decode(strval($results->getBody()), true);
        } catch (GuzzleException $e) {
            $this->logger->error('Unable to connect to Post Office Service [' . $e->getMessage() . '] ', [
                'data' => [ 'Post Code' => $postCode]
            ]);
            throw new YotiException("A connection error occurred. Previous: " . $e->getMessage());
        }
    }

    /**
     * @param array $sessionData
     * @return array
     * Create a IBV session with applicant data and requirements
     * is the endpoint there meant to be relative or a full path?
     * @throws YotiException
     * @throws YotiApiException
     */
    public function createSession(array $sessionData): array
    {
        $body = json_encode($sessionData);
        $nonce = strval(Uuid::uuid4());
        $dateTime = new DateTime();
        $timestamp = $dateTime->getTimestamp();

        $headers = $this->getSignedRequest('/sessions', 'POST', $nonce, $timestamp, $body);

        try {
            $results = $this->client->post('/idverify/v1/sessions', [
                'headers' => $headers,
                'query' => ['sdkId' => $this->sdkId->getValue(), 'nonce' => $nonce, 'timestamp' => $timestamp],
                'body' => $body
            ]);

            if ($results->getStatusCode() !== Response::STATUS_CODE_201) {
                throw new YotiApiException($results->getReasonPhrase());
            }
        } catch (ClientException $clientException) {

            $this->logger->error('Unable to connect to Yoti Service [' .$clientException->getMessage(). '] ', [
                'data' => [ 'operation' => 'session-create', 'header' => $headers]
            ]);
            throw new YotiApiException($clientException->getMessage(), 0, $clientException);
        }

        $result = json_decode(strval($results->getBody()), true);

        return ["status" => $results->getStatusCode(), "data" => $result];
    }

    /**
     * @param string $sessionId
     * @return array
     * Look up results of a Post Office IBV session
     */
    public function retrieveResults(string $sessionId): array
    {
        //can either use client directly like below or use
        $results = $this->client->get('/sessions/' . $sessionId);

        return json_decode(strval($results->getBody()), true);
    }

    /**
     * @param CaseData $caseData
     * @return array
     * PDF Stage 1: Prepare PDF letter for applicant, generatePDFLetter vs retrieveLetterPDF
     * @throws YotiException
     */
    public function preparePDFLetter(CaseData $caseData): array
    {
        $config = $this->getSessionConfigFromYoti($caseData->sessionId);
        $requirementID = $config['capture']['required_resources'][0]['id'];
        $payload = json_encode($this->letterConfigPayload($caseData, $requirementID));

        $nonce = strval(Uuid::uuid4());
        $dateTime = new DateTime();
        $timestamp = $dateTime->getTimestamp();

        $headers = $this->getSignedRequest(
            '/sessions/'.$caseData->sessionId.'/instructions',
            'PUT',
            $nonce,
            $timestamp,
            $payload,
            $caseData->sessionId
        );

        try {
            $config = $this->client->put('/idverify/v1/sessions/'. $caseData->sessionId. '/instructions', [
                'headers' => $headers,
                'query' => [
                    'sdkId' => $this->sdkId->getValue(),
                    'sessionId' => $caseData->sessionId,
                    'nonce' => $nonce,
                    'timestamp'=>$timestamp
                ],
                'body' => $payload
            ]);

            if ($config->getStatusCode() !== Response::STATUS_CODE_200) {
                $this->logger->error('PDF letter generation was unsuccessful ', [
                    'data' => [ ]
                ]);
                throw new YotiException("Error: " . $config->getReasonPhrase());
            }
            return ["status" => "PDF Created"];


        } catch (GuzzleException $e) {
            $this->logger->error('Unable to connect to Yoti service [' . $e->getMessage() . '] ', [
                'data' => [ 'operation' => 'session-create', 'header' => $headers]
            ]);
            throw new YotiException("A connection error occurred. Previous: " . $e->getMessage());
        }

    }
    /**
     * @param string $yotiSessionId
     * @return array
     * Generate PDF letter instructions
     * @throws YotiException
     */
    private function getSessionConfigFromYoti(string $yotiSessionId): array
    {

        $nonce = strval(Uuid::uuid4());
        $dateTime = new DateTime();
        $timestamp = $dateTime->getTimestamp();

        $headers = $this->getSignedRequest(
            '/sessions/'. $yotiSessionId. '/configuration',
            'GET',
            $nonce,
            $timestamp,
            null,
            $yotiSessionId
        );

        try {
            $config = $this->client->get('/idverify/v1/sessions/'. $yotiSessionId. '/configuration', [
                'headers' => $headers,
                'query' => [
                    'sdkId' => $this->sdkId->getValue(),
                    'sessionId' => $yotiSessionId,
                    'nonce' => $nonce,
                    'timestamp'=>$timestamp
                ]
            ]);

            if ($config->getStatusCode() !== Response::STATUS_CODE_200) {
                $this->logger->error('Case configuration retrieval was unsuccessful ', [
                    'data' => [ ]
                ]);
                throw new YotiException("Error: " . $config->getReasonPhrase());
            }
            return json_decode(strval($config->getBody()), true);

        } catch (GuzzleException $e) {
            $this->logger->error('Unable to connect to Yoti service [' . $e->getMessage() . '] ', [
                'data' => [ ]
            ]);
            throw new YotiException("A connection error occurred. Previous: " . $e->getMessage());
        }
    }
    /**
     * @param CaseData $caseData
     * @return array
     * PDF Stage 2: Retrieve PDF letter contents
     * @throws YotiException
     */
    public function retrieveLetterPDF(CaseData $caseData): array
    {
        //need error validation here if this is called before instructions are set etc
        $nonce = strval(Uuid::uuid4());
        $dateTime = new DateTime();
        $timestamp = $dateTime->getTimestamp();

        $headers = $this->getSignedRequest(
            '/sessions/' . $caseData->sessionId . '/instructions/pdf',
            'GET',
            $nonce,
            $timestamp,
            null,
            $caseData->sessionId
        );
        try {

            $pdfData = $this->client->get('/idverify/v1/sessions/' . $caseData->sessionId . '/instructions/pdf', [
                'headers' => $headers,
                'query' => [
                    'sdkId' => $this->sdkId->getValue(),
                    'sessionId' => $caseData->sessionId,
                    'nonce' => $nonce,
                    'timestamp' => $timestamp
                ],
            ]);
        } catch (GuzzleException $e){
            $this->logger->error('Unable to connect to Yoti service [' . $e->getMessage() . '] ', [
                'data' => [ 'operation' => 'generate-pdf', 'header' => $headers]
            ]);
            throw new YotiException("A connection error occurred. Previous: " . $e->getMessage());
        }
        $base64 = base64_encode(strval($pdfData->getBody()));
        // Convert base64 to pdf
        $pdf = base64_decode($base64);

        return ["status" => "PDF Created", "pdfData" => $pdf];
    }

    private function letterConfigPayload(CaseData $caseData, $requirementId): array
    {
        $payload = [];

        $payload["contact_profile"] = [
            "first_name" => $caseData->firstName,
            "last_name" => $caseData->lastName,
            "email" => 'opg-all-team+yoti@digital.justice.gov.uk'
        ];
        $payload["documents"] = [
            [
                "requirement_id" => $requirementId,
                "document" => [
                    "type" => "ID_DOCUMENT",
                    "country_code" => "GBR",
                    "document_type" => SessionConfig::getDocType($caseData->idMethod)
                ]
            ]
        ];
        $payload["branch"] = [
          "type" => "UK_POST_OFFICE",
          "fad_code" => $caseData->selectedPostOffice
        ];
        return $payload;
    }

    public function getSignedRequest(
        string $endpoint,
        string $method,
        string $nonce,
        int $time,
        string $body = null,
        string $sessionId = null,
    ): array
    {
        $apiEndpoint = $endpoint. '?sdkId='.$this->sdkId->getValue();
        if ($sessionId !== null) {
            $apiEndpoint = $apiEndpoint. '&sessionId='.$sessionId;
        }
        try {
            $requestSignature = RequestSigner::generateSignature(
                $apiEndpoint.'&nonce='.$nonce. '&timestamp='.$time,
                $method,
                $this->key,
                $body
            );
        } catch (Http\Exception\PemFileException $e) {
            throw new YotiException("There was a problem with Pem file");
        } catch (Http\Exception\RequestSignException $e) {
            throw new YotiException("Unable to create request signature");
        }

        $headers = [
            'X-Yoti-Auth-Digest' => $requestSignature
        ];

        return $headers;
    }
}

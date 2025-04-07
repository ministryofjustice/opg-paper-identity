<?php

declare(strict_types=1);

namespace ApplicationTest\ApplicationTest\Unit\Services;

use Application\Aws\Secrets\AwsSecret;
use Application\Enums\DocumentType;
use Application\Enums\IdRoute;
use Application\Model\Entity\CaseData;
use Application\Yoti\Http\Exception\YotiApiException;
use Application\Yoti\Http\Exception\YotiException;
use Application\Yoti\Http\RequestSigner;
use Application\Yoti\YotiService;
use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

/**
 * @psalm-suppress DeprecatedMethod
 * False positive with `GuzzleHttp\Client::__call` being deprecated
 * Supression can be removed when Guzzle 8 is released as it removes the method
 * @psalm-suppress UndefinedInterfaceMethod
 */
class YotiServiceTest extends TestCase
{
    private Client&MockObject $client;
    private LoggerInterface&MockObject $logger;
    private AwsSecret&MockObject $sdkId;
    private AwsSecret&MockObject $key;
    private YotiService $yotiService;
    private RequestSigner&MockObject $requestSigner;

    private string $notificationEmail;

    protected function setUp(): void
    {
        /**
         * @psalm-suppress InvalidPropertyAssignmentValue
         */
        $this->client = $this->createMock(Client::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->sdkId = $this->createMock(AwsSecret::class);
        $this->key = $this->createMock(AwsSecret::class);
        $this->requestSigner = $this->createMock(RequestSigner::class);

        $this->sdkId->method('getValue')->willReturn('test-sdk-id');
        $this->key->method('getValue')->willReturn('test-key');
        $this->notificationEmail = 'notifications.paper-id';

        $this->yotiService = new YotiService(
            $this->client,
            $this->logger,
            $this->sdkId,
            $this->key,
            $this->requestSigner,
            $this->notificationEmail
        );
    }

    public function testPostOfficeBranchSuccess(): void
    {
        $postCode = 'AB12CD';
        $responseBody = json_encode(['branches' => []], JSON_THROW_ON_ERROR);

        $response = new GuzzleResponse(200, [], $responseBody);
        $this->client->expects($this->once())
            ->method("post")
            ->with('/idverify/v1/lookup/uk-post-office')
            ->willReturn($response);

        $result = $this->yotiService->postOfficeBranch($postCode);

        $this->assertEquals(['branches' => []], $result);
    }

    public function testPostOfficeBranchFailure(): void
    {
        $this->expectException(YotiException::class);

        $postCode = 'AB12CD';
        $response = new GuzzleResponse(400, [], 'Bad Request');

        $this->client->expects($this->once())
            ->method('post')->willReturn($response);

        $this->yotiService->postOfficeBranch($postCode);
    }

    public function testCreateSessionSuccess(): void
    {
        $sessionData = ['data' => 'test'];
        $responseBody = json_encode(['status' => 'created'], JSON_THROW_ON_ERROR);

        $response = new GuzzleResponse(201, [], $responseBody);

        $nonce = strval(Uuid::uuid4());
        $dateTime = new DateTime();
        $timestamp = $dateTime->getTimestamp();

        $this->requestSigner->expects($this->atLeastOnce())
            ->method("generateSignature")
            ->with(
                '/sessions?sdkId=test-sdk-id&nonce=' . $nonce . '&timestamp=' . $timestamp,
                'POST',
                $this->key,
                json_encode($sessionData)
            )
            ->willReturn('signature');

        $this->client->expects($this->once())
            ->method('post')
            ->with('/idverify/v1/sessions')
            ->willReturn($response);

        $result = $this->yotiService->createSession($sessionData, $nonce, $timestamp);

        $this->assertEquals(201, $result['status']);
        $this->assertEquals(['status' => 'created'], $result['data']);
    }

    public function testCreateSessionFailure(): void
    {
        $this->expectException(YotiException::class);

        $sessionData = ['empty'];
        $nonce = strval(Uuid::uuid4());
        $dateTime = new DateTime();
        $timestamp = $dateTime->getTimestamp();

        $this->requestSigner->expects($this->atLeastOnce())
            ->method("generateSignature")
            ->with(
                '/sessions?sdkId=test-sdk-id&nonce=' . $nonce . '&timestamp=' . $timestamp,
                'POST',
                $this->key,
                json_encode($sessionData)
            )
            ->willReturn('signature');

        $response = new GuzzleResponse(400, [], 'Bad Request');

        $this->client->method('post')->willReturn($response);

        $this->yotiService->createSession($sessionData, $nonce, $timestamp);
    }

    public function testGetSessionConfigSuccess(): void
    {
        $sessionId = 'asASJFAFsd';

        $nonce = strval(Uuid::uuid4());
        $dateTime = new DateTime();
        $timestamp = $dateTime->getTimestamp();

        $this->requestSigner->expects($this->atLeastOnce())
            ->method("generateSignature")
            ->with(
                '/sessions/' . $sessionId . '/configuration?sdkId=test-sdk-id&sessionId=' . $sessionId .
                    '&nonce=' . $nonce . '&timestamp=' . $timestamp,
                'GET',
                $this->key,
                null
            )
            ->willReturn('signature');

        $config = ['capture' => ['required_resources' => [['id' => 'resource-id']]]];
        $this->client->method('get')->willReturn(new GuzzleResponse(
            200,
            [],
            json_encode($config, JSON_THROW_ON_ERROR)
        ));

        $this->yotiService->getSessionConfigFromYoti($sessionId, $nonce, $timestamp);
    }

    public function testRetrieveLetterPDFSuccess(): void
    {
        $sessionId = 'session-id';

        $nonce = strval(Uuid::uuid4());
        $dateTime = new DateTime();
        $timestamp = $dateTime->getTimestamp();

        $this->requestSigner->expects($this->atLeastOnce())
            ->method("generateSignature")
            ->with(
                '/sessions/' . $sessionId . '/instructions/pdf?sdkId=test-sdk-id&sessionId='
                . $sessionId . '&nonce=' . $nonce . '&timestamp=' . $timestamp,
                'GET',
                $this->key,
                null
            )
            ->willReturn('signature');

        $response = new GuzzleResponse(200, [], 'pdf-content');
        $this->client->method('get')->willReturn($response);

        $result = $this->yotiService->retrieveLetterPDF($sessionId, $nonce, $timestamp);

        $this->assertEquals('PDF Created', $result['status']);
        $this->assertEquals(base64_encode('pdf-content'), $result['pdfBase64']);
    }

    public function testLetterConfigPayload(): void
    {
        $caseData = CaseData::fromArray([
            'id' => '2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc',
            'claimedIdentity' => [
                'firstName' => 'Maria',
                'lastName' => 'Williams',
                'dob' => '1970-01-01',
                'address' => [
                    'line1' => '123 long street',
                ]
            ],
            'personType' => 'donor',
            "idMethod" => [
                'docType' => DocumentType::Passport->value,
                'idCountry' => "GBR",
                'idRoute' => IdRoute::POST_OFFICE->value,
            ],
            'counterService' => [
                'selectedPostOffice' => '29348729'
            ],
            'lpas' => []
        ]);

        $payload = $this->yotiService->letterConfigPayload($caseData, "1234456633");
        $this->assertEquals($this->expectedLetterConfig(), $payload);
    }
    public function expectedLetterConfig(): array
    {
        $payload = [];
        $payload["contact_profile"] = [
            "first_name" => "Maria",
            "last_name" => "Williams",
            "email" => $this->notificationEmail
        ];
        $payload["documents"] = [
            [
                "requirement_id" => "1234456633",
                "document" => [
                    "type" => "ID_DOCUMENT",
                    "country_code" => "GBR",
                    "document_type" => DocumentType::Passport->value
                ]
            ]
        ];
        $payload["branch"] = [
            "type" => "UK_POST_OFFICE",
            "fad_code" => "29348729"
        ];
        return $payload;
    }
}

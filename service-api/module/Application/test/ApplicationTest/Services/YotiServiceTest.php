<?php

declare(strict_types=1);

namespace ApplicationTest\Services;

use Application\Aws\Secrets\AwsSecret;
use Application\Model\Entity\CaseData;
use Application\Yoti\Http\Exception\YotiApiException;
use Application\Yoti\Http\Exception\YotiException;
use Application\Yoti\SessionConfig;
use Application\Yoti\YotiService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @psalm-suppress DeprecatedMethod
 * False positive with `GuzzleHttp\Client::__call` being deprecated
 * Supression can be removed when Guzzle 8 is released as it removes the method
 * @psalm-suppress UndefinedInterfaceMethod
 */
class YotiServiceTest extends TestCase
{
    private Client $client;
    private LoggerInterface $logger;
    private AwsSecret $sdkId;
    private AwsSecret $key;
    private YotiService $yotiService;

    protected function setUp(): void
    {
        $this->client = $this->createMock(Client::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->sdkId = $this->createMock(AwsSecret::class);
        $this->key = $this->createMock(AwsSecret::class);

        $this->sdkId->method('getValue')->willReturn('test-sdk-id');
        $this->key->method('getValue')->willReturn('test-key');

        $this->yotiService = new YotiService(
            $this->client,
            $this->logger,
            $this->sdkId,
            $this->key
        );
    }

    public function testPostOfficeBranchSuccess(): void
    {
        $postCode = 'AB12CD';
        $responseBody = json_encode(['branches' => []]);

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

    public function testLetterConfigPayload(): void
    {
        $caseData = CaseData::fromArray([
            'id' => '2b45a8c1-dd35-47ef-a00e-c7b6264bf1cc',
            'firstName' => 'Maria',
            'lastName' => 'Williams',
            'personType' => 'donor',
            'dob' => '1970-01-01',
            'idMethod' => 'po_ukp',
            'address' => [
                'line1' => '123 long street',
            ],
            'selectedPostOffice' => '29348729',
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
            "email" => 'opg-all-team+yoti@digital.justice.gov.uk'
        ];
        $payload["documents"] = [
            [
                "requirement_id" => "1234456633",
                "document" => [
                    "type" => "ID_DOCUMENT",
                    "country_code" => "GBR",
                    "document_type" => "PASSPORT"
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

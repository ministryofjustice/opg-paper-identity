<?php

declare(strict_types=1);

namespace ApplicationTest\ApplicationTest\Unit\Services\HMPO\HmpoApi;

use Application\Enums\DocumentType;
use Application\HMPO\AuthApi\AuthApiService;
use Application\HMPO\HmpoApi\HmpoApiService;
use Application\HMPO\HmpoApi\HmpoApiException;
use Application\Model\Entity\CaseData;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class HmpoApiServiceTest extends TestCase
{
    private Client&MockObject $client;
    private AuthApiService&MockObject $hmpoAuthApiService;
    private HmpoApiService $hmpoApiService;
    private LoggerInterface&MockObject $logger;
    private CaseData $caseData;
    private int $passportNumber;

    public function setUp(): void
    {
        /**
         * @psalm-suppress InvalidPropertyAssignmentValue
         */
        $this->client = $this->createMock(Client::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->hmpoAuthApiService = $this->createMock(AuthApiService::class);

        $this->caseData = CaseData::fromArray([
            'idMethod' => ['docType' => DocumentType::Passport->value],
            'claimedIdentity' => [
                'dob' => '2000-01-01',
                'firstName' => 'Mary Ann',
                'lastName' => 'Chapman',
            ]
        ]);
        $this->passportNumber = 123456789;

        $headerOptions = ['X-API-Key' => 'X-API-Key'];

        $this->hmpoApiService = new HmpoApiService(
            $this->client,
            $this->hmpoAuthApiService,
            $this->logger,
            $headerOptions
        );
    }

    public function testErrorWithMissingApiKey(): void
    {
        $this->expectException(HmpoApiException::class);
        $this->expectExceptionMessage('X-API-Key must be present in headerOptions');

        new HmpoApiService(
            new client(),
            $this->hmpoAuthApiService,
            $this->logger,
            []
        );
    }

    public function testvalidatePassportSuccessfulRequest(): void
    {
        $this->hmpoAuthApiService
            ->expects($this->once())
            ->method('retrieveCachedTokenResponse')
            ->willReturn('cached-token');

        $expectedHeaders = [
            'Content-Type' => 'application/json',
            'X-API-Key' => 'X-API-Key',
            'X-DVAD-NETWORK-TYPE' => 'api',
            'User-Agent' => 'hmpo-opg-client',
            'Authorization' => 'Bearer cached-token'
        ];
        $expectedBody = [
            "query" =>
                "query validatePassport(input: \$input) { validationResult passportCancelled passportLostStolen }",
            "variables" => [
                "input" => [
                    'forenames' => 'Mary Ann',
                    'surname' => 'Chapman',
                    'dateOfBirth' => '2000-01-01',
                    'passportNumber' => 123456789
                ]
            ]
        ];

        $apiResponse = new GuzzleResponse(200, [], '{"data": {"validatePassport": {"validationResult": true}}}');

        $this->client
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/graphql',
                $this->callback(function (array $options) use ($expectedHeaders, $expectedBody) {
                        $this->assertArrayIsEqualToArrayIgnoringListOfKeys(
                            $options['headers'],
                            $expectedHeaders,
                            ['X-REQUEST-ID']
                        );
                        $this->assertEquals($options['json'], $expectedBody);
                    return true;
                })
            )
            ->willReturn($apiResponse);

        $result = $this->hmpoApiService->validatePassport($this->caseData, $this->passportNumber);

        $this->assertTrue($result);
    }

    public function testValidatePassportAuthFailure(): void
    {
        $this->hmpoAuthApiService
            ->expects($this->exactly(2))
            ->method('retrieveCachedTokenResponse')
            ->willReturn('cached-token');

        $failMock = new MockHandler([
            new GuzzleResponse(401, [], json_encode(['Bad Request'], JSON_THROW_ON_ERROR)),
            new GuzzleResponse(401, [], json_encode(['Bad Request'], JSON_THROW_ON_ERROR)),

        ]);
        $handlerStack = HandlerStack::create($failMock);
        $failClient = new Client(['handler' => $handlerStack]);

        $hmpoApiService = new HmpoApiService(
            $failClient,
            $this->hmpoAuthApiService,
            $this->logger,
            ['X-API-Key' => 'X-API-Key']
        );

        $this->expectException(ClientException::class);

        $hmpoApiService->validatePassport($this->caseData, $this->passportNumber);
    }
}

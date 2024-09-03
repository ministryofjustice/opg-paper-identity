<?php

declare(strict_types=1);

namespace ApplicationTest\ApplicationTest\Services\Experian\AuthApi;

use Application\Cache\ApcHelper;
use Application\Services\Experian\AuthApi\DTO\ExperianCrosscoreAuthRequestDTO;
use Application\Services\Experian\AuthApi\ExperianCrosscoreAuthApiService;
use Application\Services\Experian\AuthApi\ExperianCrosscoreAuthApiException;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\TestCase;
use Throwable;

class ExperianCrosscoreAuthApiServiceTest extends TestCase
{
    private Client $client;

    private ApcHelper $apcHelper;

    private ExperianCrosscoreAuthRequestDTO $experianCrosscoreAuthRequestDto;

    private ExperianCrosscoreAuthApiService $experianCrosscoreAuthApiService;

    public function setUp(): void
    {
        $this->client = $this->createMock(Client::class);
        $this->apcHelper = $this->createMock(ApcHelper::class);
        $this->experianCrosscoreAuthRequestDto = new ExperianCrosscoreAuthRequestDTO(
            'username',
            'password',
            'clientId',
            'clientSecret',
        );

        $this->experianCrosscoreAuthApiService = new ExperianCrosscoreAuthApiService(
            $this->client,
            $this->apcHelper,
            $this->experianCrosscoreAuthRequestDto
        );
    }

    public function testGetHeaders(): void
    {
        $headers = $this->experianCrosscoreAuthApiService->makeHeaders();

        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertArrayHasKey('X-Correlation-Id', $headers);
        $this->assertArrayHasKey('X-User-Domain', $headers);

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $headers['X-Correlation-Id']
        );
    }

    public function testGetCredentials(): void
    {
        $credentials = $this->experianCrosscoreAuthApiService->getCredentials();

        $this->assertInstanceOf(ExperianCrosscoreAuthRequestDTO::class, $credentials);
    }

    /**
     * @dataProvider tokenResponseData
     * @param class-string<Throwable>|null $expectedException
     */
    public function testAuthenticate(Client $client, ?array $responseData, ?string $expectedException): void
    {
        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        $experianCrosscoreAuthApiService = new ExperianCrosscoreAuthApiService(
            $client,
            $this->apcHelper,
            $this->experianCrosscoreAuthRequestDto,
        );

        $response = $experianCrosscoreAuthApiService->authenticate();

        $this->assertEquals($responseData, $response->toArray());
    }

    public static function tokenResponseData(): array
    {
        $successMockResponseData = [
            "issued_at" => "1724856638",
            "expires_in" => "1800",
            "token_type" => "Bearer",
            "access_token" => "eyJraWQiOiJJSmpTMXJQQjdJODBHWjgybmNsSlZPQkF3V3B3ZTVYblNKZUdSZHdpc
                EY5IiwidHlwIjoiSldUIiwiYWxnIjoiUlMyNTYifQ.eyJzdWIiOiJTWVNURU0uVUFUQVBJQHB1YmxpY2d1YXJ
                kaWFuLmNvbSIsIkVtYWlsIjpudWxsLCJGaXJzdE5hbWUiOm51bGwsImlzcyI6IkVYUEVSSUFOIiwiTGFzdE5hb
                WUiOm51bGwsImV4cCI6MTcyNDg1ODQzOCwiaWF0IjoxNzI0ODU2NjM4LCJqdGkiOiIzNGJlODg5Ny1kYmYyLT
                QwOTctODZlMS02NTQ3ZDc1YzAwYmMifQ.oqA5HWPnyssBrfCLxQqJmFHEXpD_bRV6hX3hu5DzI4azqrGnFn_q
                27j6nsd6Urh3DdpAaETCgB3Nn074yTAKX02qIfNROpEiof8oWRbXZp89JJcI7by4mSyiXzhhzO_lTDRFnYIumz
                RlEgwyGdtq16-5GSw3m7dN0TUReXnSZdSNB1uuCkwVM9VwdPrJhAF8Uq6ECG9rft2WUXuguUA08s5bdFIAOJfOm
                6uFu5oskaCg79IO3ASdkVlOpWm8-csNCWeXLyq0ShV3jjO7XfZATiVL7zCxZF-ec",
            "refresh_token" => "6Vx6yOMXQwupKggjueitrwotnFA0o3"
        ];

        $successMock = new MockHandler([
            new GuzzleResponse(200, [], json_encode($successMockResponseData)),
        ]);
        $handlerStack = HandlerStack::create($successMock);
        $successClient = new Client(['handler' => $handlerStack]);

        $failMock = new MockHandler([
            new GuzzleResponse(401, [], json_encode(['Bad Request'])),
        ]);
        $handlerStack = HandlerStack::create($failMock);
        $failClient = new Client(['handler' => $handlerStack]);

        return [
            [
                $successClient,
                $successMockResponseData,
                null
            ],
            [
                $failClient,
                null,
                ExperianCrosscoreAuthApiException::class,
            ]
        ];
    }
}
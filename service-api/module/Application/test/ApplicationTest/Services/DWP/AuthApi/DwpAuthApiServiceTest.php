<?php

declare(strict_types=1);

namespace ApplicationTest\Services\DWP\AuthApi;

use Application\Cache\ApcHelper;
use Application\DWP\AuthApi\DTO\RequestDTO;
use Application\DWP\AuthApi\AuthApiException;
use Application\DWP\AuthApi\AuthApiService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Throwable;

class DwpAuthApiServiceTest extends TestCase
{
    private Client $client;

    private ApcHelper $apcHelper;

    private RequestDTO $dwpAuthRequestDto;

    private AuthApiService $dwpAuthApiService;

    private LoggerInterface&MockObject $logger;

    public function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->client = $this->createMock(Client::class);
        $this->apcHelper = $this->createMock(ApcHelper::class);
        $this->dwpAuthRequestDto = new RequestDTO(
            'username',
            'password',
            'bundle',
            'privateKey',
        );

        $this->dwpAuthApiService = new AuthApiService(
            $this->client,
            $this->apcHelper,
            $this->logger,
            $this->dwpAuthRequestDto
        );
    }

    public function testGetHeaders(): void
    {
        $headers = $this->dwpAuthApiService->makeHeaders();

        $this->assertArrayHasKey('Content-Type', $headers);
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

        $dwpAuthApiService = new AuthApiService(
            $client,
            $this->apcHelper,
            $this->logger,
            $this->dwpAuthRequestDto,
        );

        $response = $dwpAuthApiService->authenticate();

        $this->assertEquals($responseData, $response->toArray());
    }

    public static function tokenResponseData(): array
    {
        $successMockResponseData = [
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
                AuthApiException::class,
            ]
        ];
    }
}

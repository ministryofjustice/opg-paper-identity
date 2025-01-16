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

    private string $oauthTokenEndpoint = '';

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
            $this->dwpAuthRequestDto,
            $this->oauthTokenEndpoint
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
    public function testAuthenticate(
        Client $client,
        ?array $responseData,
        ?string $expectedException
    ): void {
        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        $dwpAuthApiService = new AuthApiService(
            $client,
            $this->apcHelper,
            $this->logger,
            $this->dwpAuthRequestDto,
            $this->oauthTokenEndpoint
        );

        $response = $dwpAuthApiService->authenticate();

        $this->assertEquals($responseData, $response->toArray());
    }

    public static function tokenResponseData(): array
    {
        $successMockResponseData = [
            "expires_in" => "1800",
            "token_type" => "Bearer",
            "access_token" => "ey-generic-access-token-for-test",
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

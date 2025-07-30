<?php

declare(strict_types=1);

namespace ApplicationTest\ApplicationTest\Unit\Services\DWP\AuthApi;

use Application\Cache\ApcHelper;
use Application\HMPO\AuthApi\AuthApiException;
use Application\HMPO\AuthApi\AuthApiService;
use Application\HMPO\AuthApi\DTO\RequestDTO;
use Application\HMPO\AuthApi\DTO\ResponseDTO;
use AWS\CRT\HTTP\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Exception;

class AuthApiServiceTest extends TestCase
{
    private Client&MockObject $client;
    private ApcHelper&MockObject $apcHelper;
    private LoggerInterface&MockObject $logger;
    private AuthApiService $authApiService;

    public function setUp(): void
    {
        /**
         * @psalm-suppress InvalidPropertyAssignmentValue
         */
        $this->client = $this->createMock(Client::class);
        $this->apcHelper = $this->createMock(ApcHelper::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $requestDto = new RequestDTO(
            'grant-type',
            'client-id',
            'client-secret',
        );

        $headerOptions = ['X-API-Key' => 'X-API-Key'];

        $this->authApiService = new AuthApiService(
            $this->client,
            $this->apcHelper,
            $this->logger,
            $requestDto,
            $headerOptions,
        );
    }

    public function testAuthenticate(): void
    {
        $mockResponseData = [
            'access_token' => 'generic-access-token-for-test',
            'expires_in' => 1800,
            'token_type' => 'Bearer',
        ];

        $mockResponse = new GuzzleResponse(200, [], json_encode($mockResponseData, JSON_THROW_ON_ERROR));
        $expectedHeaders = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'X-API-Key' => 'X-API-Key',
            'X-DVAD-NETWORK-TYPE' => 'api',
            'User-Agent' => 'hmpo-opg-client',
        ];
        $expectedFormParams = [
            'grantType' => 'grant-type',
            'clientId' => 'client-id',
            'secret' => 'client-secret'
        ];
        $this->client
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/auth/token',
                $this->callback(function (array $options) use ($expectedHeaders, $expectedFormParams) {
                    $this->assertArrayIsEqualToArrayIgnoringListOfKeys(
                        $options['headers'],
                        $expectedHeaders,
                        ['X-REQUEST-ID']
                    );
                    $this->assertEquals($options['form_params'], $expectedFormParams);
                    return true;
                })
            )
            ->willReturn($mockResponse);

        $this->apcHelper
            ->expects($this->once())
            ->method('setValue')
            ->with(
                'hmpo_access_token',
                json_encode([
                    'access_token' => 'generic-access-token-for-test',
                    'time' => (int)(new \DateTime())->format('U') + 1800,
                ])
            );

        $expectedResponse = new ResponseDTO(
            'generic-access-token-for-test',
            1800,
        );
        $response = $this->authApiService->authenticate();
        $this->assertEquals($expectedResponse, $response);
    }

    public function testAuthenticateRaiseException(): void
    {
        $this->client
            ->method('request')
            ->will($this->throwException(new Exception('something bad happened')));

        $this->expectException(AuthApiException::class);
        $this->authApiService->authenticate();
    }

    #[DataProvider('retrieveCachedTokenResponseData')]
    public function testRetrieveCachedTokenResponse(?string $cache, ?array $responseData, string $expectedToken): void
    {
        $this->apcHelper
            ->expects($this->once())
            ->method('getValue')
            ->with('hmpo_access_token')
            ->willReturn($cache);

        if (! is_null($responseData)) {
            $response = new GuzzleResponse(200, [], json_encode($responseData, JSON_THROW_ON_ERROR));
            $this->client
                ->expects($this->once())
                ->method('request')
                ->willReturn($response);
        }

        $accessToken = $this->authApiService->retrieveCachedTokenResponse();

        $this->assertEquals($accessToken, $expectedToken);
    }


    public static function retrieveCachedTokenResponseData(): array
    {
        $validToken = json_encode([
            'access_token' => 'cached-token',
            'time' => (int)(new \DateTime())->format('U') + 10,
        ]);

        $expiredToken = json_encode([
            'access_token' => 'cached-token',
            'time' => (int)(new \DateTime())->format('U') - 10,
        ]);

        $responseData = [
            'access_token' => 'newly-generated-token',
            'expires_in' => 1800,
            'refresh_expires_in' => 0,
            'token_type' => 'Bearer',
        ];

        return [
            'cache is still valid' => [$validToken, null, 'cached-token'],
            'cache is empty' => [null, $responseData, 'newly-generated-token'],
            'cache has expired' => [$expiredToken, $responseData, 'newly-generated-token'],
        ];
    }
}

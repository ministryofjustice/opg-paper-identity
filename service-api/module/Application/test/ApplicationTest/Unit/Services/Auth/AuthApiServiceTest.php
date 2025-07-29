<?php

declare(strict_types=1);

namespace ApplicationTest\ApplicationTest\Unit\Services\DWP\AuthApi;

use Application\Cache\ApcHelper;
use Application\Services\Auth\AuthApiException;
use Application\Services\Auth\AuthApiService;
use Application\Services\Auth\DTO\RequestDTO;
use Application\Services\Auth\DTO\ResponseDTO;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Exception;

// simple implementation so we can test core methods
// phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses
class ExtendedAuthApiService extends AuthApiService
{
    public function makeHeaders(): array
    {
        return [
            'headerOne' => '12345',
            'headerTwo' => 'abcde',
        ];
    }
}

// phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses
class ExtendedRequestDTO extends RequestDTO
{
    public function toArray(): array
    {
        return [
            'grant_type' => $this->grantType,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ];
    }
}

// phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses
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

        $requestDto = new ExtendedRequestDTO(
            'grant-type',
            'client-id',
            'client-secret',
        );

        $this->authApiService = new ExtendedAuthApiService(
            $this->client,
            $this->apcHelper,
            $this->logger,
            $requestDto,
            'auth/endpoint',
            'cache-name',
        );
    }

    public function testAuthenticate(): void
    {
        $mockResponseData = [
            'access_token' => 'generic-access-token-for-test',
            'expires_in' => '1800',
            'token_type' => 'Bearer',
        ];
        $mockResponse = new GuzzleResponse(200, [], json_encode($mockResponseData, JSON_THROW_ON_ERROR));
        $this->client
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'auth/endpoint',
                [
                    'headers' => [
                        'headerOne' => '12345',
                        'headerTwo' => 'abcde',
                    ],
                    'form_params' => [
                        'grant_type' => 'grant-type',
                        'client_id' => 'client-id',
                        'client_secret' => 'client-secret'
                    ]
                ]
            )
            ->willReturn($mockResponse);

        $this->apcHelper
            ->expects($this->once())
            ->method('setValue')
            ->with(
                'cache-name',
                json_encode([
                    'access_token' => 'generic-access-token-for-test',
                    'time' => (int)(new \DateTime())->format('U') + 1800,
                ])
            );

        $expectedResponse = new ResponseDTO(
            'generic-access-token-for-test',
            '1800',
            'Bearer',
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
            ->with('cache-name')
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
            'expires_in' => '1800',
            'token_type' => 'Bearer',
        ];

        return [
            'cache is still valid' => [$validToken, null, 'cached-token'],
            'cache is empty' => [null, $responseData, 'newly-generated-token'],
            'cache has expired' => [$expiredToken, $responseData, 'newly-generated-token'],
        ];
    }
}

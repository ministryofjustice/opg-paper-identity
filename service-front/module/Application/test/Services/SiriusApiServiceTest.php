<?php

declare(strict_types=1);

namespace ApplicationTest\Services;

use Application\Exceptions\PostcodeInvalidException;
use Application\Services\SiriusApiService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Laminas\Http\Headers;
use Laminas\Http\Request;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @psalm-suppress DeprecatedMethod
 * False positive with `GuzzleHttp\Client::__call` being deprecated
 * Supression can be removed when Guzzle 8 is released as it removes the method
 */
class SiriusApiServiceTest extends TestCase
{
    public function testCheckAuthSuccess(): void
    {
        $clientMock = $this->createMock(Client::class);
        $loggerMock = $this->createMock(LoggerInterface::class);

        $sut = new SiriusApiService($clientMock, $loggerMock);

        $request = new Request();
        $headers = $request->getHeaders();
        assert($headers instanceof Headers);
        $headers->addHeaderLine("cookie", "mycookie=1; XSRF-TOKEN=abcd");

        $clientMock
            ->expects($this->once())
            ->method("get")
            ->with("/api/v1/users/current", [
                'headers' => [
                    'Cookie' => 'mycookie=1; XSRF-TOKEN=abcd',
                    'X-XSRF-TOKEN' => 'abcd',
                ],
            ]);

        $ret = $sut->checkAuth($request);
        $this->assertTrue($ret);
    }

    public function testCheckAuthFailureNoCookie(): void
    {
        $clientMock = $this->createMock(Client::class);
        $loggerMock = $this->createMock(LoggerInterface::class);

        $sut = new SiriusApiService($clientMock, $loggerMock);


        $request = new Request();
        $headers = $request->getHeaders();
        assert($headers instanceof Headers);

        $ret = $sut->checkAuth($request);
        $this->assertFalse($ret);
    }

    public function testCheckAuthFailureNoXSRF(): void
    {
        $clientMock = $this->createMock(Client::class);
        $loggerMock = $this->createMock(LoggerInterface::class);

        $sut = new SiriusApiService($clientMock, $loggerMock);


        $request = new Request();
        $headers = $request->getHeaders();
        assert($headers instanceof Headers);
        $headers->addHeaderLine("cookie", "mycookie=1");

        $ret = $sut->checkAuth($request);
        $this->assertFalse($ret);
    }

    public function testCheckAuthFailureNotAuthed(): void
    {
        $clientMock = $this->createMock(Client::class);
        $loggerMock = $this->createMock(LoggerInterface::class);

        $sut = new SiriusApiService($clientMock, $loggerMock);


        $request = new Request();
        $headers = $request->getHeaders();
        assert($headers instanceof Headers);
        $headers->addHeaderLine("cookie", "mycookie=1; XSRF-TOKEN=abcd");

        $exception = $this->createMock(GuzzleException::class);

        $clientMock
            ->expects($this->once())
            ->method("get")
            ->with("/api/v1/users/current", [
                'headers' => [
                    'Cookie' => 'mycookie=1; XSRF-TOKEN=abcd',
                    'X-XSRF-TOKEN' => 'abcd',
                ],
            ])
            ->willThrowException($exception);

        $ret = $sut->checkAuth($request);
        $this->assertFalse($ret);
    }

    public function testSearchAddressesByPostcodeSuccess(): void
    {
        $clientMock = $this->createMock(Client::class);
        $loggerMock = $this->createMock(LoggerInterface::class);

        $sut = new SiriusApiService($clientMock, $loggerMock);


        $request = new Request();
        $headers = $request->getHeaders();
        assert($headers instanceof Headers);
        $headers->addHeaderLine("cookie", "mycookie=1; XSRF-TOKEN=abcd");

        $responseBody = json_encode([
            [
                'addressLine1' => '10 Downing Street',
                'addressLine2' => '',
                'addressLine3' => '',
                'town' => 'London',
                'postcode' => 'SW1A 2AA',
            ],
        ]);

        $clientMock
            ->expects($this->once())
            ->method("get")
            ->with('/api/v1/postcode-lookup?postcode=SW1A2AA', [
                'headers' => [
                    'Cookie' => 'mycookie=1; XSRF-TOKEN=abcd',
                    'X-XSRF-TOKEN' => 'abcd',
                ],
            ])
            ->willReturn(new Response(200, [], $responseBody));

        $result = $sut->searchAddressesByPostcode('SW1A2AA', $request);

        $this->assertCount(1, $result);
        $this->assertEquals('10 Downing Street', $result[0]['addressLine1']);
    }

    public function testSearchAddressesByPostcodeThrowsPostcodeInvalidException(): void
    {
        $clientMock = $this->createMock(Client::class);
        $loggerMock = $this->createMock(LoggerInterface::class);

        $sut = new SiriusApiService($clientMock, $loggerMock);


        $request = new Request();
        $headers = $request->getHeaders();
        assert($headers instanceof Headers);
        $headers->addHeaderLine("cookie", "mycookie=1; XSRF-TOKEN=abcd");

        $exception = $this->createMock(ClientException::class);
        $exception
            ->method('getResponse')
            ->willReturn(new Response(400));

        $clientMock
            ->expects($this->once())
            ->method("get")
            ->with('/api/v1/postcode-lookup?postcode=INVALID', [
                'headers' => [
                    'Cookie' => 'mycookie=1; XSRF-TOKEN=abcd',
                    'X-XSRF-TOKEN' => 'abcd',
                ],
            ])
            ->willThrowException($exception);

        $this->expectException(PostcodeInvalidException::class);
        $sut->searchAddressesByPostcode('INVALID', $request);
    }

    public function testSearchAddressesByPostcodeThrowsGenericException(): void
    {
        $clientMock = $this->createMock(Client::class);
        $loggerMock = $this->createMock(LoggerInterface::class);

        $sut = new SiriusApiService($clientMock, $loggerMock);


        $request = new Request();
        $headers = $request->getHeaders();
        assert($headers instanceof Headers);
        $headers->addHeaderLine("cookie", "mycookie=1; XSRF-TOKEN=abcd");

        $exception = $this->createMock(GuzzleException::class);

        $clientMock
            ->expects($this->once())
            ->method("get")
            ->with('/api/v1/postcode-lookup?postcode=SW1A2AA', [
                'headers' => [
                    'Cookie' => 'mycookie=1; XSRF-TOKEN=abcd',
                    'X-XSRF-TOKEN' => 'abcd',
                ],
            ])
            ->willThrowException($exception);

        $this->expectException(GuzzleException::class);
        $sut->searchAddressesByPostcode('SW1A2AA', $request);
    }
}

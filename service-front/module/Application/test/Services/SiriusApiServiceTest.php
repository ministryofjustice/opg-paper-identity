<?php

declare(strict_types=1);

namespace ApplicationTest\Services;

use Application\Services\SiriusApiService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Laminas\Http\Headers;
use Laminas\Http\Request;
use PHPUnit\Framework\TestCase;

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

        $sut = new SiriusApiService($clientMock);

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

        $sut = new SiriusApiService($clientMock);

        $request = new Request();
        $headers = $request->getHeaders();
        assert($headers instanceof Headers);

        $ret = $sut->checkAuth($request);
        $this->assertFalse($ret);
    }

    public function testCheckAuthFailureNoXSRF(): void
    {
        $clientMock = $this->createMock(Client::class);

        $sut = new SiriusApiService($clientMock);

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

        $sut = new SiriusApiService($clientMock);

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
}

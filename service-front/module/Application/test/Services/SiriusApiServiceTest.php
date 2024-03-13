<?php

declare(strict_types=1);

namespace ApplicationTest\Services;

use GuzzleHttp\Client;
use Application\Services\SiriusApiService;
use GuzzleHttp\Exception\GuzzleException;
use Laminas\Http\Headers;
use Laminas\Http\Request;
use PHPUnit\Framework\TestCase;

class SiriusApiServiceTest extends TestCase
{
    public function testCheckAuthSuccess(): void
    {
        $clientMock = $this->createMock(Client::class);

        $sut = new SiriusApiService($clientMock);

        $request = new Request();
        $headers = $request->getHeaders();
        assert($headers instanceof Headers);
        $headers->addHeaderLine("cookie", "mycookie=1");

        $clientMock
            ->expects($this->once())
            ->method("get")
            ->with("/api/v1/users/current", ['headers' => ['Cookie' => 'mycookie=1']]);

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

    public function testCheckAuthFailureNotAuthed(): void
    {
        $clientMock = $this->createMock(Client::class);

        $sut = new SiriusApiService($clientMock);

        $request = new Request();
        $headers = $request->getHeaders();
        assert($headers instanceof Headers);
        $headers->addHeaderLine("cookie", "mycookie=1");

        $exception = $this->createMock(GuzzleException::class);

        $clientMock
            ->expects($this->once())
            ->method("get")
            ->with("/api/v1/users/current", ['headers' => ['Cookie' => 'mycookie=1']])
            ->willThrowException($exception);

        $ret = $sut->checkAuth($request);
        $this->assertFalse($ret);
    }
}

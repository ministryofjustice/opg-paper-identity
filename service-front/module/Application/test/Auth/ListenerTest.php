<?php

declare(strict_types=1);

namespace ApplicationTest\Auth;

use Application\Auth\Listener;
use Exception;
use GuzzleHttp\Client;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

class ListenerTest extends AbstractHttpControllerTestCase
{
    public function testCheckAuthSuccess(): void
    {
        $client = $this->createMock(Client::class);

        $sut = new Listener($client, "login-url");

        $event = $this->createMock(MvcEvent::class);

        $request = new Request();
        $request->getHeaders()->addHeaderLine("cookie", "mycookie=1");
        $event->expects($this->once())->method("getRequest")->willReturn($request);

        $client->expects($this->once())
          ->method("get")
          ->with("/api/v1/users/current", ['headers' => ['Cookie' => 'mycookie=1']]);

        $ret = $sut->checkAuth($event);

        $this->assertNull($ret);
    }

    public function testCheckAuthFailure(): void
    {
        $client = $this->createMock(Client::class);

        $sut = new Listener($client, "http://login-url");

        $event = $this->createMock(MvcEvent::class);

        $request = new Request();
        $request->getHeaders()->addHeaderLine("cookie", "mycookie=1");
        $event->expects($this->once())->method("getRequest")->willReturn($request);

        $event->expects($this->once())->method("getResponse")->willReturn(new Response());

        $client->expects($this->once())
          ->method("get")
          ->with("/api/v1/users/current", ['headers' => ['Cookie' => 'mycookie=1']])
          ->willThrowException(new Exception("bad response"));

        $ret = $sut->checkAuth($event);

        $this->assertInstanceOf(Response::class, $ret);
        $this->assertEquals(302, $ret->getStatusCode());
        $this->assertEquals("http://login-url", $ret->getHeaders()->get('Location')->getFieldValue());
    }
}

<?php

declare(strict_types=1);

namespace ApplicationTest\Feature\Auth;

use Application\Auth\Listener;
use Application\Services\SiriusApiService;
use Laminas\Http\Header\Location;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Psr\Http\Message\RequestInterface;

class ListenerTest extends AbstractHttpControllerTestCase
{
    public function testCheckAuthSuccess(): void
    {
        $siriusApiMock = $this->createMock(SiriusApiService::class);

        $sut = new Listener($siriusApiMock, "login-url");

        $event = $this->createMock(MvcEvent::class);

        $request = new Request();

        $event->expects($this->once())->method("getRequest")->willReturn($request);

        $siriusApiMock->expects($this->once())
            ->method("checkAuth")
            ->with($this->isInstanceOf(RequestInterface::class))
            ->willReturn(true);

        $ret = $sut->checkAuth($event);

        $this->assertNull($ret);
    }

    public function testCheckAuthFailure(): void
    {
        $siriusApiMock = $this->createMock(SiriusApiService::class);

        $sut = new Listener($siriusApiMock, "https://login-url");

        $event = $this->createMock(MvcEvent::class);

        $request = new Request();
        $request->setUri('https://somehost/my/page?type=somevalue');
        $event->expects($this->once())->method("getRequest")->willReturn($request);

        $event->expects($this->once())->method("getResponse")->willReturn(new Response());

        $siriusApiMock->expects($this->once())
            ->method("checkAuth")
            ->with($this->callback(function (RequestInterface $psr7Request) {
                return $psr7Request->getUri()->__toString() === 'https://somehost/my/page?type=somevalue';
            }))
            ->willReturn(false);

        $response = $sut->checkAuth($event);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
        $location = $response->getHeaders()->get('Location');
        assert($location instanceof Location);

        $expectedUrl = "https://login-url/auth?redirect=%2Fmy%2Fpage%3Ftype%3Dsomevalue";
        $this->assertEquals($expectedUrl, $location->getFieldValue());
    }
}

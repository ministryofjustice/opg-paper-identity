<?php

declare(strict_types=1);

namespace ApplicationTest\Feature\Middleware;

use Application\Middleware\AuthMiddleware;
use Application\Services\SiriusApiService;
use ApplicationTest\Feature\Controller\BaseControllerTestCase;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthMiddlewareTest extends BaseControllerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        putenv('DISABLE_AUTH_LISTENER=0');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        putenv('DISABLE_AUTH_LISTENER=1');
    }

    public function testCheckAuthSuccess(): void
    {
        $siriusApiMock = $this->createMock(SiriusApiService::class);

        $sut = new AuthMiddleware($siriusApiMock, "login-url");

        $request = new ServerRequest();

        $siriusApiMock->expects($this->once())
            ->method("checkAuth")
            ->with($this->isInstanceOf(RequestInterface::class))
            ->willReturn(true);

        $mockHandler = $this->createMock(RequestHandlerInterface::class);
        $mockHandler->expects($this->once())->method("handle")->willReturn(new Response(status: 499));

        $response = $sut->process($request, $mockHandler);

        $this->assertEquals(499, $response->getStatusCode());
    }

    public function testCheckAuthFailure(): void
    {
        $siriusApiMock = $this->createMock(SiriusApiService::class);

        $sut = new AuthMiddleware($siriusApiMock, "https://login-url");

        $request = (new ServerRequest())
            ->withUri(new Uri('https://somehost/my/page?type=somevalue'));

        $siriusApiMock->expects($this->once())
            ->method("checkAuth")
            ->with($this->callback(function (RequestInterface $psr7Request) {
                return $psr7Request->getUri()->__toString() === 'https://somehost/my/page?type=somevalue';
            }))
            ->willReturn(false);

        $mockHandler = $this->createMock(RequestHandlerInterface::class);
        $mockHandler->expects($this->never())->method("handle");

        $response = $sut->process($request, $mockHandler);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(302, $response->getStatusCode());

        $location = $response->getHeaderLine('Location');
        $expectedUrl = "https://login-url/auth?redirect=%2Fmy%2Fpage%3Ftype%3Dsomevalue";
        $this->assertEquals($expectedUrl, $location);
    }
}

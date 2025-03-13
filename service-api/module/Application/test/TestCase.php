<?php

declare(strict_types=1);

namespace ApplicationTest;

use Application\Auth\Listener;
use Laminas\Http\Headers;
use Laminas\Http\Request as HttpRequest;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

abstract class TestCase extends AbstractHttpControllerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $listener = $this->getApplicationServiceLocator()->get(Listener::class);
        $listener->detach($this->getApplication()->getEventManager());
    }

    public function dispatchJSON(string $path, string $method, mixed $data = null): void
    {
        $headers = new Headers();
        $headers->addHeaderLine('Accept', 'application/json');
        $headers->addHeaderLine('Content-Type', 'application/json');

        /** @var HttpRequest $request */
        $request = $this->getRequest();
        $request->setHeaders($headers);
        $request->setContent(is_string($data) ? $data : json_encode($data));

        $this->dispatch($path, $method);
    }
}

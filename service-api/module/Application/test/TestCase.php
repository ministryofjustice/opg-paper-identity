<?php

declare(strict_types=1);

namespace ApplicationTest;

use Laminas\Http\Headers;
use Laminas\Http\Request as HttpRequest;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;

abstract class TestCase extends AbstractHttpControllerTestCase
{
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

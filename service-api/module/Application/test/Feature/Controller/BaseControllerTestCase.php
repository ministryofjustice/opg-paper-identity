<?php

declare(strict_types=1);

namespace ApplicationTest\Feature\Controller;

use Exception;
use Laminas\Http\Headers;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Application;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\Parameters;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Throwable;

abstract class BaseControllerTestCase extends TestCase
{
    protected ServiceManager $serviceManager;

    /** @var array<string, mixed> */
    protected array $applicationConfig = [];
    private ?Application $application = null;
    private Response $response;

    protected function setUp(): void
    {
        parent::setUp();
        if (empty($this->applicationConfig)) {
            $this->setApplicationConfig(include __DIR__ . '/../../../../../config/application.config.php');
        }

        $this->serviceManager = $this->getApplication()->getServiceManager();
        $this->response = new Response();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->serviceManager);
    }

    protected function setApplicationConfig(array $config): void
    {
        $this->applicationConfig = $config;
    }

    public function getApplicationServiceLocator(): ServiceManager
    {
        return $this->serviceManager;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    protected function getApplication(): Application
    {
        if ($this->application === null) {
            $this->application = Application::init($this->applicationConfig);
        }

        return $this->application;
    }

    public function dispatch(
        string $url,
        string $method = 'GET',
        array|string|null $body = null,
        array $requestHeaders = []
    ): void {
        /** @var Request $request */
        $request = $this->getApplication()->getRequest();

        $request->setUri($url);
        $request->setMethod($method);

        /** @var Headers $headers */
        $headers = $request->getHeaders();
        foreach ($requestHeaders as $name => $value) {
            $headers->addHeaderLine($name, $value);
        }

        $headers->addHeaderLine('Accept', 'application/json');

        if (is_string($body)) {
            $headers->addHeaderLine('Content-Type', str_starts_with($body, '<?xml') ? 'text/xml' : 'application/json');
            $request->setContent($body);
        } else {
            $headers->addHeaderLine('Content-Type', 'application/json');
            $request->setContent(json_encode($body));
        }

        // Convert query string to params if necessary
        $query = $request->getQuery()->toArray();
        $queryString = $request->getUri()->getQuery();
        if (null !== $queryString) {
            parse_str($queryString, $query);
        }
        $request->setQuery(new Parameters($query));

        // Run request
        $this->getApplication()->run();

        /** @var Response $response */
        $response = $this->getApplication()->getMvcEvent()->getResponse();

        $this->response = $response;
    }

    public function assertResponseStatusCode(int $code): void
    {
        $match = $this->getResponse()->getStatusCode();

        $this->assertEquals(
            $code,
            $match,
            sprintf('Failed asserting response code "%s", actual status code is "%s"', $code, $match)
        );
    }

    public function assertMatchedRouteName(string $route): void
    {
        $routeMatch = $this->getApplication()->getMvcEvent()->getRouteMatch();
        if (! $routeMatch) {
            throw new ExpectationFailedException($this->createFailureMessage('No route matched'));
        }
        $match = $routeMatch->getMatchedRouteName();
        $match = strtolower($match);
        $route = strtolower($route);
        if ($route !== $match) {
            throw new ExpectationFailedException($this->createFailureMessage(
                sprintf(
                    'Failed asserting matched route name was "%s", actual matched route name is "%s"',
                    $route,
                    $match
                )
            ));
        }
        $this->assertEquals($route, $match);
    }

    protected function createFailureMessage(string $message): string
    {
        $exception = $this->getApplication()->getMvcEvent()->getParam('exception');
        if (! $exception instanceof Throwable && ! $exception instanceof Exception) {
            return $message;
        }

        $messages = [];
        do {
            $messages[] = sprintf(
                "Exception '%s' with message '%s' in %s:%d",
                $exception::class,
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            );
        } while ($exception = $exception->getPrevious());

        return sprintf("%s\n\nExceptions raised:\n%s\n", $message, implode("\n\n", $messages));
    }
}

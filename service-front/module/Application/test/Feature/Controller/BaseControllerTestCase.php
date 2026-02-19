<?php

declare(strict_types=1);

namespace ApplicationTest\Feature\Controller;

use GuzzleHttp\Psr7\Utils;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Laminas\ServiceManager\ServiceManager;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DomCrawler\Crawler;

abstract class BaseControllerTestCase extends TestCase
{
    protected ServiceManager $serviceManager;
    private ResponseInterface $response;

    protected function setUp(): void
    {
        $container = include __DIR__ . '/../../../../../config/container.php';
        $this->serviceManager = $container;

        $app = $container->get(\Mezzio\Application::class);

        // Execute programmatic/declarative middleware pipeline and routing
        // configuration statements
        (include __DIR__ . '/../../../../../config/pipeline.php')($app);
        (include __DIR__ . '/../../../../../config/routes.php')($app);

        $this->response = new Response();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->serviceManager);
    }

    public function getApplicationServiceLocator(): ServiceManager
    {
        return $this->serviceManager;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function dispatch(string $url, string $method = 'GET', array|string|null $body = null): void
    {
        $query = [];
        $queryString = parse_url($url, PHP_URL_QUERY);
        if (is_string($queryString)) {
            parse_str($queryString, $query);
        }

        $request = (new ServerRequest())
            ->withUri(new Uri($url))
            ->withMethod($method)
            ->withQueryParams($query);

        if (is_string($body)) {
            $request = $request->withBody(Utils::streamFor($body));
        } elseif (is_array($body)) {
            $request = $request
                ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
                ->withParsedBody($body)
                ->withBody(Utils::streamFor(http_build_query($body)));
        }

        /** @var \Mezzio\Application $app */
        $app = $this->getApplicationServiceLocator()->get(\Mezzio\Application::class);

        $this->response = $app->handle($request);
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
        $routeMatch = $this->getApplicationServiceLocator()->get(UrlHelper::class)->getRouteResult();
        if (! $routeMatch) {
            throw new ExpectationFailedException('No route matched');
        }
        $match = $routeMatch->getMatchedRouteName();
        $match = strtolower($match);
        $route = strtolower($route);
        if ($route !== $match) {
            throw new ExpectationFailedException(
                sprintf(
                    'Failed asserting matched route name was "%s", actual matched route name is "%s"',
                    $route,
                    $match
                )
            );
        }
        $this->assertEquals($route, $match);
    }

    public function assertRedirectTo(string $url): void
    {
        $locationHeader = $this->getResponse()->getHeaderLine('Location');
        if ($locationHeader === '') {
            throw new ExpectationFailedException('Failed asserting redirect, no Location header found');
        }

        if ($url !== $locationHeader) {
            throw new ExpectationFailedException(
                sprintf('Failed asserting redirect to "%s", actual redirect is to "%s"', $url, $locationHeader)
            );
        }
        $this->assertEquals($url, $locationHeader);
    }

    private function query(string $query): Crawler
    {
        $response = $this->getResponse();
        $document = new Crawler(strval($response->getBody()));

        return $document->filter($query);
    }

    public function assertQuery(string $query): void
    {
        $element = $this->query($query);

        $this->assertGreaterThan(
            0,
            $element->count(),
            sprintf('Failed asserting element exists for query "%s"', $query)
        );
    }

    public function assertQueryCount(string $query, int $count): void
    {
        $element = $this->query($query);

        $this->assertEquals(
            $count,
            $element->count(),
            sprintf(
                'Failed asserting element count for query "%s", expected "%s", actual "%s"',
                $query,
                $count,
                $element->count()
            )
        );
    }

    public function assertQueryContentContains(string $query, string $content): void
    {
        $element = $this->query($query);

        $this->assertGreaterThan(
            0,
            $element->count(),
            sprintf('Failed asserting element exists for query "%s"', $query)
        );

        foreach ($element as $domElement) {
            $match = $domElement->textContent;
            if (str_contains($match, $content)) {
                $this->assertStringContainsString($content, $match);

                return;
            }
        }

        throw new ExpectationFailedException(
            sprintf(
                'Failed asserting content for query "%s" contains "%s"',
                $query,
                $content,
            )
        );
    }

    /**
     * @param non-empty-string $pattern
     */
    public function assertQueryContentRegex(string $query, string $pattern): void
    {
        $element = $this->query($query);

        $this->assertGreaterThan(
            0,
            $element->count(),
            sprintf('Failed asserting element exists for query "%s"', $query)
        );

        foreach ($element as $domElement) {
            $match = $domElement->textContent;
            if (preg_match($pattern, $match)) {
                $this->assertMatchesRegularExpression($pattern, $match);

                return;
            }
        }

        throw new ExpectationFailedException(
            sprintf(
                'Failed asserting content for query "%s" matches pattern "%s"',
                $query,
                $pattern,
            )
        );
    }
}

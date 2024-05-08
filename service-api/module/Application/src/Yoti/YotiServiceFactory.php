<?php

declare(strict_types=1);

namespace Application\Yoti;

use Application\Mock\Yoti\YotiService as MockYotiService;
use GuzzleHttp\Client;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;

class YotiServiceFactory implements FactoryInterface
{
    /**
     * Could potentially have one service class with different baseUrls, still need YOTI client ID details
     * to use their RequestBuilder
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array<mixed>|null $options
     * @return YotiServiceInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): YotiServiceInterface
    {
        /** @var bool $useMock */
        $useMock = getenv("MOCK_YOTI");
        if ($useMock) {
            $baseUri = getenv("LOCAL_YOTI_BASE_URL");
            $client = new Client(['base_uri' => $baseUri]);

            return new MockYotiService($client);
        }

        $baseUri = getenv("YOTI_BASE_URL");
        if (! is_string($baseUri) || empty($baseUri)) {
            throw new RuntimeException("YOTI_BASE_URL is empty");
        }
        $client = new Client(['base_uri' => $baseUri]);

        return new YotiService($client);
    }
}

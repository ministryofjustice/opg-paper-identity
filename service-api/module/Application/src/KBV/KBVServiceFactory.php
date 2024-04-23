<?php

declare(strict_types=1);

namespace Application\KBV;

use Application\Mock\KBV\KBVService as MockKBVService;
use GuzzleHttp\Client;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;

class KBVServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array<mixed>|null $options
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): KBVServiceInterface
    {
        /** @var bool $useMock */
        $useMock = getenv("MOCK_KBV_API");
        if ($useMock) {
            return new MockKBVService();
        }

        $baseUri = getenv("EXPERIAN_BASE_URL");
        if (! is_string($baseUri) || empty($baseUri)) {
            throw new RuntimeException("EXPERIAN_BASE_URL is empty");
        }
        $client = new Client(['base_uri' => $baseUri]);

        return new KBVService($client);
    }
}

<?php

declare(strict_types=1);

namespace Application\Factories;


use Application\Services\Contract\NINOServiceInterface;
use Application\Services\MockNinoService;
use Application\Services\NinoAPIService;
use GuzzleHttp\Client;
use RuntimeException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;


class NinoAPIServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string                          $requestedName
     * @param array<mixed>|null               $options
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): ?NINOServiceInterface
    {

        $useMock = getenv("MOCK_NINO_API");
        if ($useMock) {
            return new MockNinoService();
        }
        //@TODO implement real API service
        $baseUri = getenv("NINO_API_BASE_URL");
        if (! is_string($baseUri) || empty($baseUri)) {
            throw new RuntimeException("NINO_BASE_URL is empty");
        }

        $client = new Client(['base_uri' => $baseUri]);

        return new NinoAPIService(
            $client
        );
    }
}

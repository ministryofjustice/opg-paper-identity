<?php

declare(strict_types=1);

namespace Application\Factories;

use GuzzleHttp\Client;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Application\Services\ExperianCrosscoreApiService;
use RuntimeException;

class ExperianCrosscoreApiServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string                          $requestedName
     * @param array<mixed>|null               $options
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): ExperianCrosscoreApiService
    {
        $authUri = getenv("EXPERIAN_AUTH_URL");
        if (! is_string($authUri) || empty($authUri)) {
            throw new RuntimeException("EXPERIAN_AUTH_URL is empty");
        }

        $baseUri = getenv("EXPERIAN_BASE_URI");
        if (! is_string($baseUri) || empty($baseUri)) {
            throw new RuntimeException("EXPERIAN_BASE_URI is empty");
        }

        $guzzleClient = new Client([
            'auth_uri' => $authUri,
            'base_uri' => $baseUri
        ]);

        return new ExperianCrosscoreApiService($guzzleClient);
    }
}

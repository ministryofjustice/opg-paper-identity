<?php

declare(strict_types=1);

namespace Application\Factories;

use GuzzleHttp\Client;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Application\Services\Experian\AuthApi\ExperianCrosscoreAuthApiService;
use RuntimeException;

class ExperianCrosscoreAuthApiServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string                          $requestedName
     * @param array<mixed>|null               $options
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): ExperianCrosscoreAuthApiService
    {
        $authUri = getenv("EXPERIAN_AUTH_URL");
        if (! is_string($authUri) || empty($authUri)) {
            throw new RuntimeException("EXPERIAN_AUTH_URL is empty");
        }

        $guzzleClient = new Client([
            'auth_uri' => $authUri
        ]);

        return new ExperianCrosscoreAuthApiService($guzzleClient);
    }
}

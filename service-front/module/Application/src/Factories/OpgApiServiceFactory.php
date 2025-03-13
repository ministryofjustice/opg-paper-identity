<?php

declare(strict_types=1);

namespace Application\Factories;

use Application\Auth\JwtGenerator;
use Application\Services\OpgApiService;
use GuzzleHttp\Client;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;

class OpgApiServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string                          $requestedName
     * @param array<mixed>|null               $options
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): OpgApiService
    {
        $baseUri = getenv("API_BASE_URI");
        if (! is_string($baseUri) || empty($baseUri)) {
            throw new RuntimeException("API_BASE_URI is empty");
        }

        $guzzleClient = new Client(['base_uri' => $baseUri]);

        return new OpgApiService(
            $guzzleClient,
            $container->get(JwtGenerator::class),
        );
    }
}

<?php

declare(strict_types=1);

namespace Application\Factories;

use Application\Services\SiriusApiService;
use GuzzleHttp\Client;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Psr\Log\LoggerInterface;

class SiriusApiServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string                          $requestedName
     * @param array<mixed>|null               $options
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): SiriusApiService
    {
        $baseUri = getenv("SIRIUS_BASE_URL");
        if (! is_string($baseUri) || empty($baseUri)) {
            throw new RuntimeException("SIRIUS_BASE_URL is empty");
        }

        $client = new Client(['base_uri' => $baseUri]);
        $logger = $container->get(LoggerInterface::class);

        return new SiriusApiService(
            $client,
            $logger
        );
    }
}

<?php

declare(strict_types=1);

namespace Application\Factories;

use GuzzleHttp\Client;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Application\Services\OpgApiService;

class OpgApiServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string                          $requestedName
     * @param array<mixed>|null               $options
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): OpgApiService
    {
        $baseUri = getenv("SIRIUS_BASE_URI");
        if (! is_string($baseUri) || empty($baseUri)) {
            throw new \Exception("SIRIUS_BASE_URI is empty");
        }

        $guzzleClient = new Client(['base_uri' => $baseUri]);

        return new OpgApiService($guzzleClient);
    }
}

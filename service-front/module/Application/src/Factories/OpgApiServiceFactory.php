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
        $guzzleClient = new Client(['base-url' => 'http://api-web']);

        return new OpgApiService($guzzleClient);
    }
}

<?php

declare(strict_types=1);

namespace Application\HMPO\Factories;

use Application\Aws\Secrets\AwsSecret;
use Application\HMPO\AuthApi\HmpoAuthApiService;
use Application\HMPO\HmpoApi\HmpoApiException;
use Application\HMPO\HmpoApi\HmpoApiService;
use GuzzleHttp\Client;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class HmpoApiServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array<mixed>|null $options
     * @throws HmpoApiException
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null,
    ): HmpoApiService {
        $baseUri = getenv("HMPO_BASE_URI");

        if ($baseUri === false) {
            throw new HmpoApiException("HMPO base URI is empty");
        }

        $clientOptions = [
            'base_uri' => $baseUri,
        ];

        $guzzleClient = new Client($clientOptions);

        $logger = $container->get(LoggerInterface::class);

        $headerOptions = [
            'X-API-Key' => (new AwsSecret('hmpo/api-key'))->getValue(),
        ];

        return new HmpoApiService(
            $guzzleClient,
            $container->get(HmpoAuthApiService::class),
            $logger,
            $headerOptions,
        );
    }
}

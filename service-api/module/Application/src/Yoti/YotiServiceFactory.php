<?php

declare(strict_types=1);

namespace Application\Yoti;

use Application\Aws\Secrets\AwsSecret;
use GuzzleHttp\Client;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
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

        $baseUri = getenv("YOTI_BASE_URL");
        if (! is_string($baseUri) || empty($baseUri)) {
            throw new RuntimeException("YOTI_BASE_URL is empty");
        }
        $client = new Client(['base_uri' => $baseUri]);

        $sdkId = new AwsSecret('yoti/sdk-client-id');
        $privateKey = new AwsSecret('yoti/certificate');

        return new YotiService($client, $container->get(LoggerInterface::class), $sdkId, $privateKey);
    }
}

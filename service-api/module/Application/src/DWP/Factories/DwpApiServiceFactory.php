<?php

declare(strict_types=1);

namespace Application\DWP\Factories;

use Application\Aws\Secrets\AwsSecret;
use Application\Aws\SsmHandler;
use Application\Cache\ApcHelper;
use Application\DWP\AuthApi\DTO\RequestDTO;
use Application\DWP\AuthApi\AuthApiException;
use Application\DWP\AuthApi\AuthApiService;
use Application\DWP\DwpApi\DwpApiService;
use GuzzleHttp\Client;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class DwpApiServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string                          $requestedName
     * @param array<mixed>|null               $options
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): DwpApiService {
        $baseUri = getenv("DWP");
        if (! is_string($baseUri) || empty($baseUri)) {
            throw new FraudApiException("EXPERIAN_CROSSCORE_BASE_URL is empty");
        }

        $guzzleClient = new Client([
            'base_uri' => $baseUri
        ]);

        $domain = (new AwsSecret('experian-crosscore/domain'))->getValue();
        $tenantId = (new AwsSecret('experian-crosscore/tenant-id'))->getValue();
        $logger = $container->get(LoggerInterface::class);

        return new FraudApiService(
            $guzzleClient,
            $container->get(AuthApiService::class),
            $logger,
            [
                'domain' => $domain,
                'tenantId' => $tenantId
            ]
        );
    }
}

<?php

declare(strict_types=1);

namespace Application\Factories;

use Application\Aws\Secrets\AwsSecret;
use Application\Cache\ApcHelper;
use Application\Experian\Crosscore\AuthApi\DTO\RequestDTO;
use Application\Experian\Crosscore\AuthApi\AuthApiException;
use Application\Experian\Crosscore\AuthApi\AuthApiService;
use Application\Experian\Crosscore\FraudApi\FraudApiException;
use Application\Experian\Crosscore\FraudApi\FraudApiService;
use GuzzleHttp\Client;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ExperianCrosscoreFraudApiServiceFactory implements FactoryInterface
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
    ): FraudApiService {
        $baseUri = getenv("EXPERIAN_CROSSCORE_BASE_URL");
        if (! is_string($baseUri) || empty($baseUri)) {
            throw new FraudApiException("EXPERIAN_CROSSCORE_BASE_URL is empty");
        }

        $guzzleClient = new Client([
            'base_uri' => $baseUri
        ]);

        $domain = (new AwsSecret('experian-crosscore/domain'))->getValue();
        $tenantId = (new AwsSecret('experian-crosscore/tenant-id'))->getValue();

        return new FraudApiService(
            $guzzleClient,
            $container->get(AuthApiService::class),
            [
                'domain' => $domain,
                'tenantId' => $tenantId
            ]
        );
    }
}

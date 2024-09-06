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
        $authBaseUri = getenv("EXPERIAN_CROSSCORE_AUTH_URL");
        if (! is_string($authBaseUri) || empty($authBaseUri)) {
            throw new AuthApiException("EXPERIAN_CROSSCORE_AUTH_URL is empty");
        }

        $guzzleAuthClient = new Client([
            'base_uri' => $authBaseUri
        ]);

        $baseUri = getenv("EXPERIAN_CROSSCORE_BASE_URL");
        if (! is_string($baseUri) || empty($baseUri)) {
            throw new FraudApiException("EXPERIAN_CROSSCORE_BASE_URL is empty");
        }

        $guzzleClient = new Client([
            'base_uri' => $baseUri
        ]);

        $apcHelper = new ApcHelper();

        $username = (new AwsSecret('experian-crosscore/username'))->getValue();
        $password = (new AwsSecret('experian-crosscore/password'))->getValue();
        $clientId = (new AwsSecret('experian-crosscore/client-id'))->getValue();
        $clientSecret = (new AwsSecret('experian-crosscore/client-secret'))->getValue();
        $domain = (new AwsSecret('experian-crosscore/domain'))->getValue();
        $tenantId = (new AwsSecret('experian-crosscore/tenant-id'))->getValue();

        $experianCrosscoreAuthRequestDTO = new RequestDTO(
            $username,
            $password,
            $clientId,
            $clientSecret
        );

        $experianCrosscoreAuthApiService = new AuthApiService(
            $guzzleAuthClient,
            $apcHelper,
            $experianCrosscoreAuthRequestDTO
        );

        return new FraudApiService(
            $guzzleClient,
            $experianCrosscoreAuthApiService,
            [
                'domain' => $domain,
                'tenantId' => $tenantId
            ]
        );
    }
}

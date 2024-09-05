<?php

declare(strict_types=1);

namespace Application\Factories;

use Application\Aws\Secrets\AwsSecret;
use Application\Cache\ApcHelper;
use Application\Services\Experian\AuthApi\DTO\ExperianCrosscoreAuthRequestDTO;
use GuzzleHttp\Client;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Application\Services\Experian\AuthApi\ExperianCrosscoreAuthApiService;
use Application\Services\Experian\AuthApi\ExperianCrosscoreAuthApiException;

class ExperianCrosscoreAuthApiServiceFactory implements FactoryInterface
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
    ): ExperianCrosscoreAuthApiService {
        $baseUri = getenv("EXPERIAN_CROSSCORE_AUTH_URL");
        if (! is_string($baseUri) || empty($baseUri)) {
            throw new ExperianCrosscoreAuthApiException("EXPERIAN_CROSSCORE_AUTH_URL is empty");
        }

        $guzzleClient = new Client([
            'base_uri' => $baseUri
        ]);

        $apcHelper = new ApcHelper();

        $username = (new AwsSecret('experian-crosscore/username'))->getValue();
        $password = (new AwsSecret('experian-crosscore/password'))->getValue();
        $clientId = (new AwsSecret('experian-crosscore/client-id'))->getValue();
        $clientSecret = (new AwsSecret('experian-crosscore/client-secret'))->getValue();

        $experianCrosscoreAuthRequestDTO = new ExperianCrosscoreAuthRequestDTO(
            $username,
            $password,
            $clientId,
            $clientSecret
        );

        return new ExperianCrosscoreAuthApiService(
            $guzzleClient,
            $apcHelper,
            $experianCrosscoreAuthRequestDTO
        );
    }
}

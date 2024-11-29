<?php

declare(strict_types=1);

namespace Application\Factories;

use Application\Aws\Secrets\AwsSecret;
use Application\Cache\ApcHelper;
use Application\Experian\Crosscore\AuthApi\DTO\RequestDTO;
use Application\Experian\Crosscore\AuthApi\AuthApiException;
use Application\Experian\Crosscore\AuthApi\AuthApiService;
use GuzzleHttp\Client;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

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
    ): AuthApiService {
        $baseUri = getenv("EXPERIAN_CROSSCORE_AUTH_URL");
        if (! is_string($baseUri) || empty($baseUri)) {
            throw new AuthApiException("EXPERIAN_CROSSCORE_AUTH_URL is empty");
        }

        $guzzleClient = new Client([
            'base_uri' => $baseUri
        ]);

        $logger = $container->get(LoggerInterface::class);

        $apcHelper = new ApcHelper();

        $username = (new AwsSecret('experian-crosscore/username'))->getValue();
        $password = (new AwsSecret('experian-crosscore/password'))->getValue();
        $clientId = (new AwsSecret('experian-crosscore/client-id'))->getValue();
        $clientSecret = (new AwsSecret('experian-crosscore/client-secret'))->getValue();

        $experianCrosscoreAuthRequestDTO = new RequestDTO(
            $username,
            $password,
            $clientId,
            $clientSecret
        );

        return new AuthApiService(
            $guzzleClient,
            $apcHelper,
            $logger,
            $experianCrosscoreAuthRequestDTO
        );
    }
}

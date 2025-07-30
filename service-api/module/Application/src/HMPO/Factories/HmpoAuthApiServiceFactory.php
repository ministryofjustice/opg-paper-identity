<?php

declare(strict_types=1);

namespace Application\HMPO\Factories;

use Application\Aws\Secrets\AwsSecret;
use Application\Cache\ApcHelper;
use Application\HMPO\AuthApi\DTO\RequestDTO;
use Application\HMPO\AuthApi\AuthApiException;
use Application\HMPO\AuthApi\AuthApiService;
use GuzzleHttp\Client;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class HmpoAuthApiServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array<mixed>|null $options
     * @throws AuthApiException
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): AuthApiService {
        $logger = $container->get(LoggerInterface::class);
        $baseUri = getenv("HMPO_BASE_URI");

        if ($baseUri === false) {
            throw new AuthApiException("HMPO base URI is empty");
        }

        $clientOptions = [
            'base_uri' => $baseUri,
        ];

        $guzzleClient = new Client($clientOptions);

        $apcHelper = new ApcHelper();

        $clientId = (new AwsSecret('hmpo/auth-client-id'))->getValue();
        $clientSecret = (new AwsSecret('hmpo/auth-client-secret'))->getValue();
        $grantType = 'client_credentials';

        $AuthRequestDTO = new RequestDTO(
            $grantType,
            $clientId,
            $clientSecret
        );

        $apiKey = (new AwsSecret('hmpo/api-key'))->getValue();

        $headerOptions = [
            'X-API-Key' => $apiKey,
        ];

        return new AuthApiService(
            $guzzleClient,
            $apcHelper,
            $logger,
            $AuthRequestDTO,
            $headerOptions,
        );
    }
}

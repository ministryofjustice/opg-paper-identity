<?php

declare(strict_types=1);

namespace Application\HMPO\Factories;

use Application\Aws\Secrets\AwsSecret;
use Application\Cache\ApcHelper;
use Application\HMPO\AuthApi\HmpoAuthApiService;
use Application\HMPO\AuthApi\DTO\HmpoRequestDTO;
use Application\Services\Auth\AuthApiException;
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
    ): HmpoAuthApiService {
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

        $requestArray = [
            'grant-type' => 'client_credentials',
            'client-id' => (new AwsSecret('hmpo/auth-client-id'))->getValue(),
            'client-secret' => (new AwsSecret('hmpo/auth-client-secret'))->getValue(),
        ];

        $requestDTO = new HmpoRequestDTO($requestArray);

        $apiKey = (new AwsSecret('hmpo/api-key'))->getValue();

        $headerOptions = [
            'X-API-Key' => $apiKey,
        ];

        return new HmpoAuthApiService(
            $guzzleClient,
            $apcHelper,
            $logger,
            $requestDTO,
            $headerOptions,
        );
    }
}

<?php

declare(strict_types=1);

namespace Application\DWP\Factories;

use Application\Aws\Secrets\AwsSecret;
use Application\Aws\SsmHandler;
use Application\Cache\ApcHelper;
use Application\DWP\AuthApi\DTO\RequestDTO;
use Application\DWP\AuthApi\AuthApiException;
use Application\DWP\AuthApi\AuthApiService;
use GuzzleHttp\Client;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class DwpAuthApiServiceFactory implements FactoryInterface
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
        $logger = $container->get(LoggerInterface::class);

        try {
            $baseUri = (new AwsSecret('dwp/oauth-token-endpoint'))->getValue();
            //local/paper-identity/dwp/oauth-token-endpoint
        } catch (\Exception $exception) {
            $baseUri = 'http://localhost:8089/citizen-information/oauth2/token';
            $logger->info('dwp/oauth-token-endpoint could not be found');
        }

        /**
         * @psalm-suppress TypeDoesNotContainType
         */
        if (! is_string($baseUri) || empty($baseUri)) {
            throw new AuthApiException("DWP_AUTH_URL is empty");
        }

        $guzzleClient = new Client([
            'base_uri' => $baseUri,
            'verify' => false,
        ]);

        $apcHelper = new ApcHelper();

        $bundle = (new AwsSecret('dwp/opg-certificate-bundle'))->getValue();
        $privateKey = (new AwsSecret('dwp/opg-certificate-private-key'))->getValue();
        $clientId = (new AwsSecret('dwp/oauth-client-id'))->getValue();
        $clientSecret = (new AwsSecret('dwp/oauth-client-secret'))->getValue();

        $dwpAuthRequestDTO = new RequestDTO(
            $clientId,
            $clientSecret,
            $bundle,
            $privateKey
        );

        return new AuthApiService(
            $guzzleClient,
            $apcHelper,
            $logger,
            $dwpAuthRequestDTO
        );
    }
}

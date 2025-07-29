<?php

declare(strict_types=1);

namespace Application\DWP\Factories;

use Application\Aws\Secrets\AwsSecret;
use Application\Cache\ApcHelper;
use Application\DWP\AuthApi\DTO\DwpRequestDTO;
use Application\DWP\AuthApi\DwpAuthApiService;
use Application\Services\Auth\AuthApiException;
use GuzzleHttp\Client;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class DwpAuthApiServiceFactory implements FactoryInterface
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
    ): DwpAuthApiService {
        $logger = $container->get(LoggerInterface::class);
        $baseUri = getenv("DWP_BASE_URI");

        if ($baseUri === false) {
            throw new AuthApiException("DWP base URI is empty");
        }

        $suppressCertificate = filter_var(
            getenv("DWP_SUPPRESS_CERTIFICATE"),
            FILTER_VALIDATE_BOOLEAN
        );

        $clientOptions = [
            'base_uri' => $baseUri,
        ];

        if (! $suppressCertificate) {
            $clientOptions['cert'] = '/opg-private/dwp-cert.pem';
            $clientOptions['ssl_key'] = '/opg-private/dwp-sslkey.pem';
        }

        $guzzleClient = new Client($clientOptions);

        $apcHelper = new ApcHelper();

        $dwpAuthRequestDTO = new DwpRequestDTO(
            'client_credentials',
            (new AwsSecret('dwp/oauth-client-id'))->getValue(),
            (new AwsSecret('dwp/oauth-client-secret'))->getValue(),
        );
        $authEndpoint = '/citizen-information/oauth2/token';
        $cacheName = 'dwp_access_token';

        return new DwpAuthApiService(
            $guzzleClient,
            $apcHelper,
            $logger,
            $dwpAuthRequestDTO,
            $authEndpoint,
            $cacheName,
        );
    }
}

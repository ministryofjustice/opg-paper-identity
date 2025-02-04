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
        $baseUri = (new AwsSecret('dwp/base-uri'))->getValue();
        $oauthTokenEndpoint = (new AwsSecret('dwp/oauth-token-endpoint'))->getValue();
//        $certificateBundle = (new AwsSecret('dwp/opg-certificate-bundle'))->getValue();
//        $sslKey = (new AwsSecret('dwp/opg-certificate-private-key'))->getValue();

        if (empty($baseUri)) {
            throw new AuthApiException("DWP oauth-token-endpoint is empty");
        }

        $useCertificate = filter_var(getenv("DWP_USE_CERTIFICATE"), FILTER_VALIDATE_BOOLEAN);

        $clientOptions = [
            'base_uri' => $baseUri,
        ];

        $cacertPemFilename = '/opg-private/dwp-cacert.pem';
        $sslKeyPemFilename = '/opg-private/dwp-sslkey.pem';
        $certPemFilename = '/opg-private/dwp-cert.pem';

        if ($useCertificate) {
            $clientOptions['cert'] = $certPemFilename;
            $clientOptions['ssl_key'] = $sslKeyPemFilename;
            $clientOptions['verify'] = $cacertPemFilename;
        }

        $guzzleClient = new Client($clientOptions);

        $apcHelper = new ApcHelper();

//        $bundle = (new AwsSecret('dwp/opg-certificate-bundle'))->getValue();
//        $privateKey = (new AwsSecret('dwp/opg-certificate-private-key'))->getValue();
        $clientId = (new AwsSecret('dwp/oauth-client-id'))->getValue();
        $clientSecret = (new AwsSecret('dwp/oauth-client-secret'))->getValue();
        $grantType = 'client_credentials';

        $dwpAuthRequestDTO = new RequestDTO(
            $grantType,
            $clientId,
            $clientSecret
        );

        return new AuthApiService(
            $guzzleClient,
            $apcHelper,
            $logger,
            $dwpAuthRequestDTO,
            $oauthTokenEndpoint
        );
    }
}

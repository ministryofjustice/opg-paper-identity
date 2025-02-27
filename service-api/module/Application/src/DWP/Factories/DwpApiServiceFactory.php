<?php

declare(strict_types=1);

namespace Application\DWP\Factories;

use Application\Aws\Secrets\AwsSecret;
use Application\Aws\SsmHandler;
use Application\Cache\ApcHelper;
use Application\DWP\AuthApi\DTO\RequestDTO;
use Application\DWP\AuthApi\AuthApiException;
use Application\DWP\AuthApi\AuthApiService;
use Application\DWP\DwpApi\DwpApiException;
use Application\DWP\DwpApi\DwpApiService;
use GuzzleHttp\Client;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class DwpApiServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array<mixed>|null $options
     * @throws DwpApiException
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): DwpApiService {
        $baseUri = getenv("DWP_BASE_URI");
        $dwpContext = (new AwsSecret('dwp/dwp-context'))->getValue();
        $dwpPolicyId = (new AwsSecret('dwp/dwp-policy-id'))->getValue();

        if ($baseUri === false) {
            throw new DwpApiException("DWP base URI is empty");
        }

        $headerOptions = [];

        $headerOptions['policy_id'] = $dwpPolicyId;
        $headerOptions['context'] = $dwpContext;

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

        $logger = $container->get(LoggerInterface::class);

        return new DwpApiService(
            $guzzleClient,
            $container->get(AuthApiService::class),
            $logger,
            $headerOptions
        );
    }
}

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
        $baseUri = (new AwsSecret('dwp/base-uri'))->getValue();
        $detailsPath = (new AwsSecret('dwp/citizen-details-endpoint'))->getValue();
        $matchPath = (new AwsSecret('dwp/citizen-match-endpoint'))->getValue();
        $certificate = (new AwsSecret('dwp/opg-certificate'))->getValue();
        $sslKey = (new AwsSecret('dwp/opg-certificate-private-key'))->getValue();

        if (empty($baseUri)) {
            throw new DwpApiException("DWP Citizen endpoint is empty");
        }

        $useCertificate = filter_var(getenv("DWP_USE_CERTIFICATE"), FILTER_VALIDATE_BOOLEAN);
        $clientOptions = [
            'base_uri' => $baseUri,
        ];

        if ($useCertificate) {
            $clientOptions['cert'] = $certificate;
            $clientOptions['ssl_key'] = $sslKey;
        }

        $guzzleClient = new Client($clientOptions);

        $logger = $container->get(LoggerInterface::class);

        return new DwpApiService(
            $guzzleClient,
            $container->get(AuthApiService::class),
            $logger,
            $detailsPath,
            $matchPath
        );
    }
}

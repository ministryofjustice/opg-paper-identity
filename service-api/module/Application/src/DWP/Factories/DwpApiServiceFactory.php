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

        if (empty($baseUri)) {
            throw new DwpApiException("DWP Citizen endpoint is empty");
        }

        $guzzleClient = new Client([
            'base_uri' => $baseUri,
            'verify' => false
        ]);

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

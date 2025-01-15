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
     * @param string                          $requestedName
     * @param array<mixed>|null               $options
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): DwpApiService {

        $baseUriCitizenDetails = (new AwsSecret('dwp/citizen-endpoint'))->getValue();
        $baseUriCitizenMatch = (new AwsSecret('dwp/citizen-match-endpoint'))->getValue();

        if (! is_string($baseUriCitizenDetails) || empty($baseUriCitizenDetails)) {
            throw new DwpApiException("DWP Citizen endpoint is empty");
        }

        if (! is_string($baseUriCitizenMatch) || empty($baseUriCitizenMatch)) {
            throw new DwpApiException("DWP Citizen Match endpoint is empty");
        }

        $guzzleClientCitizenDetails = new Client([
            'base_uri' => $baseUriCitizenDetails
        ]);

        $guzzleClientCitizenMatch = new Client([
            'base_uri' => $baseUriCitizenMatch
        ]);

        $logger = $container->get(LoggerInterface::class);

        return new DwpApiService(
            $guzzleClientCitizenMatch,
            $guzzleClientCitizenDetails,
            $container->get(AuthApiService::class),
            $logger
        );
    }
}

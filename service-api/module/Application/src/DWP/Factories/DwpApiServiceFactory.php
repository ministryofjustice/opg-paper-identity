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

//        $baseUriCitizen = (new AwsSecret('dwp/citizen-endpoint'))->getValue();
//        $baseUriMatch = (new AwsSecret('dwp/citizen-match-endpoint'))->getValue();

        //https://external-test.integr-dev.dwpcloud.uk:8443/capi/v2/citizens/{guid}/citizens
        //https://external-test.integr-dev.dwpcloud.uk:8443/capi/v2/citizens/match

        $baseUriCitizen = 'http://dwp-mock:8080/';

        $baseUriMatch  = 'http://dwp-mock:8080/capi/v2/citizens/match';

        if (! is_string($baseUriCitizen) || empty($baseUriCitizen)) {
            throw new DwpApiException("DWP Citizen endpoint is empty");
        }

        if (! is_string($baseUriMatch) || empty($baseUriMatch)) {
            throw new DwpApiException("DWP Citizen Match endpoint is empty");
        }

        $guzzleClientCitizen = new Client([
            'base_uri' => $baseUriCitizen
        ]);

        $guzzleClientMatch = new Client([
            'base_uri' => $baseUriMatch
        ]);

        $logger = $container->get(LoggerInterface::class);

        return new DwpApiService(
            $guzzleClientCitizen,
            $guzzleClientMatch,
            $container->get(AuthApiService::class),
            $logger
        );
    }
}

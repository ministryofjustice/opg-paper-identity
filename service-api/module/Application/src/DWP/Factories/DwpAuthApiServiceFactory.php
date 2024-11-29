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
        $ssmHandler = $container->get(SsmHandler::class);
        $logger = $container->get(LoggerInterface::class);
        $baseUri = getenv("DWP_AUTH_URL");
        if (! is_string($baseUri) || empty($baseUri)) {
            throw new AuthApiException("DWP_AUTH_URL is empty");
        }

        $guzzleClient = new Client([
            'base_uri' => $baseUri
        ]);

        $apcHelper = new ApcHelper();

        $username = (new AwsSecret('dwp/username'))->getValue();
        $password = (new AwsSecret('dwp/password'))->getValue();
        $clientId = (new AwsSecret('dwp/client-id'))->getValue();
        $clientSecret = (new AwsSecret('dwp/client-secret'))->getValue();

        $dwpAuthRequestDTO = new RequestDTO(
            $username,
            $password,
            $clientId,
            $clientSecret
        );

        return new AuthApiService(
            $guzzleClient,
            $apcHelper,
            $ssmHandler,
            $logger,
            $dwpAuthRequestDTO
        );
    }
}

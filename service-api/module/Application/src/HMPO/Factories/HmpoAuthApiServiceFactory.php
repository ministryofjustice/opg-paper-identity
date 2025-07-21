<?php

declare(strict_types=1);

namespace Application\HMPO\Factories;

use Application\Cache\ApcHelper;
use Application\HMPO\AuthApi\DTO\RequestDTO;
use Application\HMPO\AuthApi\AuthApiException;
use Application\HMPO\AuthApi\AuthApiService;
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
    ): AuthApiService {
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

        // presumably we'd stick these in AWS?
        $clientId = 'client-id';
        $clientSecret = 'client-secret';
        $grantType = 'grant-type';

        $AuthRequestDTO = new RequestDTO(
            $grantType,
            $clientId,
            $clientSecret
        );

        return new AuthApiService(
            $guzzleClient,
            $apcHelper,
            $logger,
            $AuthRequestDTO
        );
    }
}

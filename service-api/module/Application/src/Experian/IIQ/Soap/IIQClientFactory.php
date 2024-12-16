<?php

declare(strict_types=1);

namespace Application\Experian\IIQ\Soap;

use Application\Aws\Secrets\AwsSecretsCache;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class IIQClientFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     * @param null|array<mixed> $options
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): IIQClient
    {
        $logger = $container->get(LoggerInterface::class);
        $wsdlPath = getenv('EXPERIAN_IIQ_WSDL');

        if (! is_string($wsdlPath)) {
            throw new RuntimeException('Env var EXPERIAN_IIQ_WSDL must be set');
        }

        $logger->info('EXPERIAN_IIQ_WSDL: ' . $wsdlPath);
        $awsSecretsCache = $container->get(AwsSecretsCache::class);
        $passphrase = $awsSecretsCache->getValue('experian-idiq/certificate-key-passphrase');

        $pemFilePath = '/opg-private/experian-iiq-cert.pem';

        $config = [];
        if (file_exists($pemFilePath)) {
            $config['local_cert'] = $pemFilePath;
            $config['passphrase'] = $passphrase;
        }

        $client = new IIQClient(
            $wsdlPath,
            $config,
        );

        $endpoint = getenv('EXPERIAN_IIQ_LOCATION');

        if (is_string($endpoint)) {
            $logger->info('EXPERIAN_IIQ_LOCATION: ' . $endpoint);
            $client->__setLocation($endpoint);
        }

        return $client;
    }
}

<?php

declare(strict_types=1);

namespace Application\Experian\IIQ;

use Application\Aws\Secrets\AwsSecretsCache;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;

class WaspClientFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     * @param null|array<mixed> $options
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): WaspClient
    {
        $wsdlPath = getenv('EXPERIAN_IIQ_AUTH_WSDL');
        if (! is_string($wsdlPath)) {
            throw new RuntimeException('Env var EXPERIAN_IIQ_AUTH_WSDL must be set');
        }

        $awsSecretsCache = $container->get(AwsSecretsCache::class);

        $fullCert = $awsSecretsCache->getValue('experian-idiq/certificate')
                    . "\n\n"
                    . $awsSecretsCache->getValue('experian-idiq/certificate-key');
        $passphrase = $awsSecretsCache->getValue('experian-idiq/certificate-key-passphrase');

        // $config = json_decode(isset($_GET['config']) ? $_GET['config'] : '[]', true);
        $config = [];

        file_put_contents('/tmp/cert', $fullCert);

        $client = new WaspClient($wsdlPath, array_merge([
            'local_cert' => '/tmp/cert',
            'passphrase' => $passphrase,
        ], $config));

        $endpoint = getenv('EXPERIAN_IIQ_AUTH_LOCATION');
        if (is_string($endpoint)) {
            $client->__setLocation($endpoint);
        }

        return $client;
    }
}

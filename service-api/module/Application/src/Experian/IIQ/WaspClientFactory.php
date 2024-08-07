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
        $awsSecretsCache = $container->get(AwsSecretsCache::class);

        $wsdlPath = getenv('EXPERIAN_IIQ_AUTH_WSDL');
        if (!is_string($wsdlPath)) {
            throw new RuntimeException('Env var EXPERIAN_IIQ_AUTH_WSDL must be set');
        }

        $fullCert = $awsSecretsCache->getValue('experian-idiq/certificate')
                    . "\n\n"
                    . $awsSecretsCache->getValue('experian-idiq/certificate-key');
        $passphrase = $awsSecretsCache->getValue('experian-idiq/certificate-key-passphrase');

        $config = json_decode($_GET['config'], true);

        $file = tmpfile();
        fwrite($file, $fullCert);
        $tmpFilename = stream_get_meta_data($file)['uri'];

        $client = new WaspClient($wsdlPath, array_merge([
            'local_cert' => $tmpFilename,
            'passphrase' => $passphrase,
        ], $config));

        fclose($file);

        $endpoint = getenv('EXPERIAN_IIQ_AUTH_LOCATION');
        if (is_string($endpoint)) {
            $client->__setLocation($endpoint);
        }

        return $client;
    }
}

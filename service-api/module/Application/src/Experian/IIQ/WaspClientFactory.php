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

        $wsdlPath = 'https://secure.wasp.uat.uk.experian.com/WASPAuthenticator/tokenService.asmx?wsdl';

        $fullCert = $awsSecretsCache->getValue('experian-idiq/certificate')
                    . "\n\n"
                    . $awsSecretsCache->getValue('experian-idiq/certificate-key');

        $file = tmpfile();
        fwrite($file, $fullCert);
        $tmpFilename = stream_get_meta_data($file)['uri'];

        $client = new WaspClient($wsdlPath, [
            'local_cert' => $tmpFilename,
            'passphrase' => $awsSecretsCache->getValue('experian-idiq/certificate-key-passphrase'),
        ]);

        fclose($file);

        $endpoint = getenv('EXPERIAN_IIQ_AUTH_LOCATION');
        if (is_string($endpoint)) {
            $client->__setLocation($endpoint);
        } else {
            $client->__setLocation('https://secure.wasp.uat.uk.experian.com/WASPAuthenticator/tokenService.asmx');
        }

        return $client;
    }
}

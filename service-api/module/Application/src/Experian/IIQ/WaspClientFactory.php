<?php

declare(strict_types=1);

namespace Application\Experian\IIQ;

use Application\Aws\Secrets\AwsSecretsCache;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

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
        $passphrase = $awsSecretsCache->getValue('experian-idiq/certificate-key-passphrase');

        error_log('Number of lines in cert: ' . substr_count($fullCert, "\n"));
        error_log('Passphrase length:  ' . strlen($passphrase));

        $ch = curl_init($wsdlPath);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        $curl_errno = curl_errno($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_errno > 0) {
            error_log("cURL Error ($curl_errno): $curl_error");
        } else {
            error_log("Data received: " . strlen(strval($data)));
        }

        $file = tmpfile();
        fwrite($file, $fullCert);
        $tmpFilename = stream_get_meta_data($file)['uri'];

        $client = new WaspClient($wsdlPath, [
            'local_cert' => $tmpFilename,
            'passphrase' => $passphrase,
        ]);

        fclose($file);

        $endpoint = getenv('EXPERIAN_IIQ_AUTH_LOCATION');
        if (is_string($endpoint)) {
            $client->__setLocation($endpoint);
        }

        return $client;
    }
}

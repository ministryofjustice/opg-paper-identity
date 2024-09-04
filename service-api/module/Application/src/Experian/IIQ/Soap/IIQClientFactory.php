<?php

declare(strict_types=1);

namespace Application\Experian\IIQ\Soap;

use Application\Aws\Secrets\AwsSecretsCache;
use Application\Experian\IIQ\WaspService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;
use SoapHeader;
use SoapVar;

class IIQClientFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     * @param null|array<mixed> $options
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): IIQClient
    {
        $wsdlPath = getenv('EXPERIAN_IIQ_WSDL');
        if (! is_string($wsdlPath)) {
            throw new RuntimeException('Env var EXPERIAN_IIQ_WSDL must be set');
        }

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
            $client->__setLocation($endpoint);
        }

        $waspService = $container->get(WaspService::class);
        $client->__setSoapHeaders([
            $this->getSecurityHeader($waspService->loginWithCertificate()),
        ]);

        return $client;
    }

    private function getSecurityHeader(string $token): SoapHeader
    {
        $wsseNamespace = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
        $encType = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary';

        return new SoapHeader($wsseNamespace, 'Security', new SoapVar(['
            <wsse:Security xmlns:wsse="' . $wsseNamespace . '">
                <wsse:BinarySecurityToken
                    EncodingType="' . $encType . '"
                    ValueType="ExperianWASP"
                    wsu:Id="SecurityToken-9e855049-1dc9-477a-ab9a-7f7d164132ca"
                    xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd"
                >' . $token . '</wsse:BinarySecurityToken>
            </wsse:Security>
        '], XSD_ANYXML));
    }
}

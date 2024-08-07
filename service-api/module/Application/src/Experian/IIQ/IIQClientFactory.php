<?php

declare(strict_types=1);

namespace Application\Experian\IIQ;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
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

        $client = new IIQClient($wsdlPath);

        $endpoint = getenv('EXPERIAN_IIQ_LOCATION');
        if (is_string($endpoint)) {
            $client->__setLocation($endpoint);
        }

        $tokenManager = $container->get(TokenManager::class);
        $logger = $container->get(LoggerInterface::class);

        $token = $tokenManager->getToken();

        $logger->info('Successfully generated a token, ' . strlen($token) . ' characters long');

        if (strpos($token, ' ') !== false) {
            $logger->info('Token does contain a space');
        }

        $wsseNamespace = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
        $encType = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary';
        $securityHeader = new SoapHeader($wsseNamespace, 'Security', new SoapVar(['
            <wsse:Security xmlns:wsse="' . $wsseNamespace . '">
                <wsse:BinarySecurityToken
                    EncodingType="' . $encType . '"
                    ValueType="ExperianWASP"
                    wsu:Id="SecurityToken-9e855049-1dc9-477a-ab9a-7f7d164132ca"
                    xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd"
                >' . $token . '</wsse:BinarySecurityToken>
            </wsse:Security>
        '], XSD_ANYXML));

        $client->__setSoapHeaders([$securityHeader]);

        return $client;
    }
}

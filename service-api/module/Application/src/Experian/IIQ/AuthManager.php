<?php

declare(strict_types=1);

namespace Application\Experian\IIQ;

use Laminas\Cache\Storage\StorageInterface;
use SoapHeader;
use SoapVar;

class AuthManager
{
    private const string CACHE_KEY = 'experian:iiq:auth-token';

    public function __construct(
        private readonly StorageInterface $storage,
        private readonly WaspService $waspService,
    ) {
    }

    private function getToken(): string
    {
        if ($this->storage->hasItem(self::CACHE_KEY)) {
            return $this->storage->getItem(self::CACHE_KEY);
        }

        $token = $this->waspService->loginWithCertificate();

        $this->storage->setItem(self::CACHE_KEY, $token);

        return $token;
    }

    public function buildSecurityHeader(bool $forceNewToken = false): SoapHeader
    {
        if ($forceNewToken) {
            $this->storage->removeItem(self::CACHE_KEY);
        }

        $token = $this->getToken();

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

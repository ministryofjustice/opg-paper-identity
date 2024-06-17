<?php

declare(strict_types=1);

namespace Yoti\Http;

use Application\Aws\Secrets\AwsSecret;
use Yoti\Http\Exception\RequestSignException;

class RequestSigner
{
    /**
     * Return request signed data.
     *
     * @param string $endpoint
     * @param string $httpMethod
     * @param \Yoti\Http\Payload|NULL $payload
     *
     * @return string
     *   The base64 encoded signed message
     *
     * @throws \Yoti\Http\Exception\RequestSignException
     */
    public static function generateSignature(

        string $endpoint,
        string $httpMethod,
        array $payload = null
    ): string {
        $messageToSign = "{$httpMethod}&$endpoint";
        if ($payload instanceof Payload) {
            $messageToSign .= "&{$payload->toBase64()}";
        }

        $pemFile = new AwsSecret('yoti/certificate');

        openssl_sign($messageToSign, $signature, $pemFile->getValue(), OPENSSL_ALGO_SHA256);

        if (!$signature) {
            throw new RequestSignException('Could not sign request.');
        }

        return base64_encode($signature);
    }
}

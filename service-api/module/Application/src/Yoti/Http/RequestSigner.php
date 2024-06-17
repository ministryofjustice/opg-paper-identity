<?php

declare(strict_types=1);

namespace Yoti\Http;

use Application\Aws\Secrets\AwsSecret;
use Yoti\Http\Exception\RequestSignException;

/**
 * Suppress pslam error til class is used
 * @psalm-suppress UnusedClass
 */
class RequestSigner
{
    /**
     * Generates a signature for a given API endpoint and HTTP method.
     *
     * This function creates a message to sign by concatenating the HTTP method and endpoint.
     * If a payload is provided and is an instance of the Payload class, it converts the payload
     * to a base64 encoded string and appends it to the message.
     * The message is then signed using a PEM certificate fetched from AWS Secrets Manager.
     *
     * @param string $endpoint The API endpoint to be accessed.
     * @param string $httpMethod The HTTP method to be used (e.g., GET, POST).
     * @param Payload|null $payload An optional payload to include in the signature.
     *
     * @return string The generated signature, base64 encoded.
     *
     * @throws RequestSignException If the signing process fails.
     */
    public static function generateSignature(
        string $endpoint,
        string $httpMethod,
        Payload $payload = null
    ): string {
        $messageToSign = "{$httpMethod}&$endpoint";
        if ($payload instanceof Payload) {
            $messageToSign .= "&{$payload->toBase64()}";
        }

        $pemFile = new AwsSecret('yoti/certificate');

        openssl_sign($messageToSign, $signature, $pemFile->getValue(), OPENSSL_ALGO_SHA256);

        if (! $signature) {
            throw new RequestSignException('Could not sign request.');
        }

        return base64_encode($signature);
    }
}

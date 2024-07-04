<?php

declare(strict_types=1);

namespace Application\Yoti\Http;

use Application\Aws\Secrets\AwsSecret;
use Application\Yoti\Http\Exception\PemFileException;
use Application\Yoti\Http\Exception\RequestSignException;
use Application\Yoti\Http\Payload;

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
     * @throws PemFileException
     */
    public static function generateSignature(
        string $endpoint,
        string $httpMethod,
        AwsSecret $pemFile,
        string $payload = null
    ): string {
        $messageToSign = "$httpMethod&$endpoint";
        if ($payload) {
            $messageToSign.="&".base64_encode($payload);
        }

        if ($pemFile->getValue() === '') {
            throw new PemFileException('Unable to get pemFile or is empty');
        }
        $realDevSecret = file_get_contents(__DIR__ .'/private-key.pem');

        openssl_sign($messageToSign, $signature, (string) $realDevSecret, OPENSSL_ALGO_SHA256);

        if (! $signature) {
            throw new RequestSignException('Could not sign request.');
        }

        return base64_encode($signature);
    }
}

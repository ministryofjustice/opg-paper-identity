<?php

declare(strict_types=1);

namespace Application\Yoti\Http;

use Application\Aws\Secrets\AwsSecret;
use Application\Yoti\Http\Exception\YotiAuthException;

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
     * @param String|null $payload An optional payload to include in the signature.
     *
     * @return string The generated signature, base64 encoded.
     *
     * If the signing process fails.
     * @throws YotiAuthException
     */
    public function generateSignature(
        string $endpoint,
        string $httpMethod,
        AwsSecret $pemFile,
        string $payload = null
    ): string {

        $messageToSign = "{$httpMethod}&{$endpoint}";
        if ($payload !== null) {
            $messageToSign .= "&" . base64_encode($payload);
        }
        if ($pemFile->getValue() === '') {
            throw new YotiAuthException('Unable to get pemFile or is empty');
        }

        openssl_sign($messageToSign, $signature, $pemFile->getValue(), OPENSSL_ALGO_SHA256);

        if (! $signature) {
            throw new YotiAuthException('Could not sign request.');
        }

        return base64_encode($signature);
    }
}

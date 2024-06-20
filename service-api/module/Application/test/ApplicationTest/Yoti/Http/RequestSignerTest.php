<?php

declare(strict_types=1);

namespace ApplicationTest\Yoti\Http;

use Application\Aws\Secrets\AwsSecret;
use Application\Yoti\Http\Exception\PemFileException;
use Application\Yoti\Http\RequestSigner;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Application\Yoti\Http\Exception\RequestSignException;
use Application\Yoti\Http\Payload;

class RequestSignerTest extends TestCase
{
    private Payload|MockObject $payloadMock;
    private AwsSecret|MockObject $pemFileMock;
    private AwsSecretsCache|MockObject $awsCacheMock;

    private const PATH = '/api/endpoint';
    private const METHOD = 'POST';
    public function setUp(): void
    {
        $this->payloadMock = $this->createMock(Payload::class);
        $this->pemFileMock = $this->createMock(AwsSecret::class);

    }

    /**
     * @covers ::generateSignature
     */
    public function testGenerateSignature()
    {
        $this->payloadMock->method('toBase64')->willReturn('payloadBase64String');

        $this->pemFileMock->expects($this->atLeastOnce())
            ->method("getValue")
            ->willReturn($this->returnTestPemKey());

        $signedMessage = RequestSigner::generateSignature(
            self::PATH,
            self::METHOD,
            $this->pemFileMock,
            $this->payloadMock,
        );
        $messageToSign = self::METHOD . '&' . self::PATH . '&' . $this->payloadMock->toBase64();

        $publicKey = openssl_pkey_get_public($this->returnPublicKey());

        $verify = openssl_verify($messageToSign, base64_decode($signedMessage), $publicKey, OPENSSL_ALGO_SHA256);

        $this->assertEquals(1, $verify);
    }
    public function testGenerateSignatureWithValidPayload()
    {
        $this->payloadMock->method('toBase64')->willReturn('payloadBase64String');

        $this->pemFileMock->expects($this->atLeastOnce())
            ->method("getValue")
            ->willReturn($this->returnTestPemKey());

        // Generate signature
        $signature = RequestSigner::generateSignature(
            '/api/endpoint', 'POST', $this->pemFileMock, $this->payloadMock);

        // Assert the signature is a base64 encoded string
        $this->assertNotEmpty($signature);
        $this->assertIsString($signature);
        $this->assertTrue(base64_decode($signature, true) !== false);
    }

    public function testGenerateSignatureWithoutPayload()
    {
        $this->pemFileMock->expects($this->atLeastOnce())
            ->method("getValue")
            ->willReturn($this->returnTestPemKey());

        // Generate signature
        $signature = RequestSigner::generateSignature(
            '/api/endpoint', 'GET', $this->pemFileMock);

        // Assert the signature is a base64 encoded string
        $this->assertNotEmpty($signature);
        $this->assertIsString($signature);
        $this->assertTrue(base64_decode($signature, true) !== false);
    }

    public function testGenerateSignatureWithEmptyPemFile()
    {
        $this->payloadMock->method('toBase64')->willReturn('payloadBase64String');

        $this->pemFileMock->method('getValue')->willReturn('');

        $this->expectException(PemFileException::class);

        RequestSigner::generateSignature(
            '/api/endpoint', 'POST', $this->pemFileMock, $this->payloadMock);

    }


    public function returnTestPemKey(): string
    {
        return "-----BEGIN RSA PRIVATE KEY-----
MIIEoQIBAAKCAQBbQ/PGZ/A3m1fhIPLQ+Q9+AwtOxFxB+/x/cUy5/WUj3TzZ8kFG
Z26hQrRT4zERSQMie7S9ZbPi0JXoE1HlFsD6vWCqM0WN0enxNA02ek/RKiitp0v6
Q1ZxPGjBT1h2l9n0dnqTzrGbUpHTtXv3zSWYobdtgptcVJDlXSF/XdxdIzprujjX
sHjeWf6H3gSPtJRBsgexM9hS+6Ta1pmbfNo39BCG96hGhDNN7gE3xeBUimeJuylz
NWTo+IgGzsWsRPyeKe029f+aIfWdxwFlbeaF387gCWnXR8IyXNLCDAhpr4wupKNF
aCZDUg1i9Bw/QnTvXF0QkmjXUB7Qb7V7B7qpAgMBAAECggEAOe7DkqEtwg6Q1S52
FCLVK7dA+Un6CkSrfjZsbu+jwQVR+EMoHknP1vuhvlJMNl2zaLNAAq3JZ2PilIOX
C6XK8B9Aeim7sA+cwei5rmgrvGlXkwvMVdtixtSC5pq4W+d+igifPK4K3b6nJM1i
GOWXRPD6n8A1YIGpzH62ocPx+wh7lvbT2XwFkzZT+8rrSvO74k8SukcGg6WCTPuB
TdoD8v72zuDi3Lpyi8BaK0eYSErNZxeC/5tdy/qjisaO6t0vQ8t6mBXvvZv8xTp+
hLoDI0VMbHXptMvt4cRowzmJoshFk/uZZeUkD2t92wGzFwbvAHzFsmIT8ScKLoWW
qwQskQKBgQCpiaHHMIXy+Jx7fzXWcKA8VZiRlf6hM3NVNDOlLtPlnn0ENnR/+/W4
hcrv2F4qknFzbX/M0PNIZZ3e5PEwO6a8EnHBWNEP/MkAj42NkTCtS8UvD5z86juC
dmdY1KZ2REMLZdvm2tTdmTBqBy+KLDD0jLrzAhZ/KdHO9j6QL3lb7QKBgQCJz1Fn
Wx6XNNJyDhXKECTvULZyuDcATUj6Q+0foJuHgbMyD+/zQUARSBRDNrfjpA+Ndgob
aovsKU+ANSgjP9GXdfL/kei0ZO0IBq2pE13ep7RwFmnjnssjfyu+E1z8wc1cLKUv
CcfOUceD+LbqOldTzJSD1vEwH7Mzblshlj6aLQKBgCCyIVgH3J1aItuSUfC0McLR
AyZ4le3CvWheM+OUX1s2MIgCdH9GOUJH0zZkNOzi5yxKns4CMhjxN/wHjRgvON2m
dPfDyDXcG2uXQ8ZcjNWu+i00RqNkDOwBJ7cy85N1YLSvBTTFWS4PYA3iquFr2lkf
VuKMsYf+qa7PQIuQDEiVAoGABpnCeWPY5D8ocUQRcRsy2a+Q/Y+rOr146FvGiMRF
jsj8j0JKKOmQKwO7zLhbOHEMOadUtpl02DvmTeq94GpXHJ0OpYUUk0dePwsq2DVQ
QrDfqJq6OafKbQnTS4hb5NNXhbmxs74RLuWl28FW6YMf2air2GC8LqTmDWmUvdgX
aYUCgYAWKfJ81DtCGQpChlOaw84K+buliOJXO80zOFOttjOEWp16uSLFxTSlHZpl
IT1h0HmaZnAjS4PcvrUh3xXGjRE+8GoLZIDrOZUq+T8KTvsR6ZpIr6UENs3bgcxp
6gdhIIaRymiCWqLg45QwNQ6bMgWYzQy/FbT3nLLY3nqyfDLIrA==
-----END RSA PRIVATE KEY-----";
    }

    public function returnPublicKey(): string
    {
        return "-----BEGIN PUBLIC KEY-----
MIIBITANBgkqhkiG9w0BAQEFAAOCAQ4AMIIBCQKCAQBbQ/PGZ/A3m1fhIPLQ+Q9+
AwtOxFxB+/x/cUy5/WUj3TzZ8kFGZ26hQrRT4zERSQMie7S9ZbPi0JXoE1HlFsD6
vWCqM0WN0enxNA02ek/RKiitp0v6Q1ZxPGjBT1h2l9n0dnqTzrGbUpHTtXv3zSWY
obdtgptcVJDlXSF/XdxdIzprujjXsHjeWf6H3gSPtJRBsgexM9hS+6Ta1pmbfNo3
9BCG96hGhDNN7gE3xeBUimeJuylzNWTo+IgGzsWsRPyeKe029f+aIfWdxwFlbeaF
387gCWnXR8IyXNLCDAhpr4wupKNFaCZDUg1i9Bw/QnTvXF0QkmjXUB7Qb7V7B7qp
AgMBAAE=
-----END PUBLIC KEY-----";
    }
}

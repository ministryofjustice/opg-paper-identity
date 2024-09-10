<?php

declare(strict_types=1);

namespace ApplicationTest\Yoti\Http;

use Application\Aws\Secrets\AwsSecret;
use Application\Yoti\Http\Exception\YotiAuthException;
use Application\Yoti\Http\RequestSigner;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RequestSignerTest extends TestCase
{
    private AwsSecret|MockObject $pemFileMock;
    private static string $pemKeyPrivate;
    private static string $pemKeyPublic;
    private const string PATH = '/api/endpoint';
    private const string METHOD = 'POST';
    private RequestSigner $sut;

    public static function setUpBeforeClass(): void
    {
        $privateKey = openssl_pkey_new(['private_key_bits' => 2048]);
        self::$pemKeyPublic = openssl_pkey_get_details(openssl_pkey_get_private($privateKey))['key'];

        openssl_pkey_export(openssl_pkey_get_private($privateKey), $privateKeyContents);
        self::$pemKeyPrivate = $privateKeyContents;
    }


    public function setUp(): void
    {
        $this->pemFileMock = $this->createMock(AwsSecret::class);

        $this->sut = new RequestSigner();
    }

    /**
     * @covers ::generateSignature
     * @psalm-suppress PossiblyUndefinedMethod
     * @psalm-suppress PossiblyInvalidArgument
     */
    public function testGenerateSignature(): void
    {
        $payload = json_encode(['data' => 'payload']);

        $this->pemFileMock->expects($this->atLeastOnce())
            ->method("getValue")
            ->willReturn(self::$pemKeyPrivate);

        $signedMessage = $this->sut->generateSignature(
            self::PATH,
            self::METHOD,
            $this->pemFileMock,
            $payload
        );
        $messageToSign = self::METHOD . '&' . self::PATH . '&' . base64_encode($payload);

        $publicKey = openssl_pkey_get_public(self::$pemKeyPublic);

        $verify = openssl_verify($messageToSign, base64_decode($signedMessage), $publicKey, OPENSSL_ALGO_SHA256);

        $this->assertEquals(1, $verify);
    }
    /**
     * @psalm-suppress PossiblyUndefinedMethod
     * @psalm-suppress PossiblyInvalidArgument
     */
    public function testGenerateSignatureWithoutPayload(): void
    {
        $this->pemFileMock->expects($this->atLeastOnce())
            ->method("getValue")
            ->willReturn(self::$pemKeyPrivate);

        // Generate signature
        $signature = $this->sut->generateSignature(
            '/api/endpoint',
            'GET',
            $this->pemFileMock
        );

        // Assert the signature is a base64 encoded string
        $this->assertNotEmpty($signature);
        $this->assertTrue(base64_decode($signature, true) !== false);
    }
    /**
     * @psalm-suppress PossiblyUndefinedMethod
     * @psalm-suppress PossiblyInvalidArgument
     */
    public function testGenerateSignatureEmptyPemFileThrowsException(): void
    {
        $this->pemFileMock->method('getValue')->willReturn('');

        $this->expectException(YotiAuthException::class);

        $this->sut->generateSignature(
            '/api/endpoint',
            'POST',
            $this->pemFileMock,
            'payloadBase64String'
        );
    }
}

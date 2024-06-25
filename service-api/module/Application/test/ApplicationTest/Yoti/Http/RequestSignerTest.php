<?php

declare(strict_types=1);

namespace ApplicationTest\Yoti\Http;

use Application\Aws\Secrets\AwsSecret;
use Application\Yoti\Http\Exception\PemFileException;
use Application\Yoti\Http\RequestSigner;
use Aws\Result;
use Aws\SecretsManager\SecretsManagerClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Application\Yoti\Http\Exception\RequestSignException;
use Application\Yoti\Http\Payload;

class RequestSignerTest extends TestCase
{
    private Payload|MockObject $payloadMock;
    private AwsSecret|MockObject $pemFileMock;
    private SecretsManagerClient $secretsManagerClient;
    private Result $privateResult;
    private const PATH = '/api/endpoint';
    private const METHOD = 'POST';
    public function setUp(): void
    {
        $this->payloadMock = $this->createMock(Payload::class);
        $this->pemFileMock = $this->createMock(AwsSecret::class);

        $this->secretsManagerClient = new SecretsManagerClient([
            'endpoint' => getenv('SECRETS_MANAGER_ENDPOINT'),
            'region' => 'eu-west-1'
        ]);
        $this->privateResult = $this->secretsManagerClient->getSecretValue([
            'SecretId' => 'local/paper-identity/private-key',
        ]);
    }

    /**
     * @covers ::generateSignature
     * @psalm-suppress PossiblyUndefinedMethod
     * @psalm-suppress PossiblyInvalidArgument
     */
    public function testGenerateSignature(): void
    {
        $this->payloadMock->method('toBase64')->willReturn('payloadBase64String');

        $this->pemFileMock->expects($this->atLeastOnce())
            ->method("getValue")
            ->willReturn($this->privateResult['SecretString']);

        $signedMessage = RequestSigner::generateSignature(
            self::PATH,
            self::METHOD,
            $this->pemFileMock,
            $this->payloadMock,
        );
        $messageToSign = self::METHOD . '&' . self::PATH . '&' . $this->payloadMock->toBase64();
        /** @var array $publicKeyResult */
        $publicKeyResult = $this->secretsManagerClient->getSecretValue([
            'SecretId' => 'local/paper-identity/public-key',
        ]);

        $publicKey = openssl_pkey_get_public($publicKeyResult['SecretString']);

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
            ->willReturn($this->privateResult["SecretString"]);

        // Generate signature
        $signature = RequestSigner::generateSignature(
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
        $this->payloadMock->method('toBase64')->willReturn('payloadBase64String');

        $this->pemFileMock->method('getValue')->willReturn('');

        $this->expectException(PemFileException::class);

        RequestSigner::generateSignature(
            '/api/endpoint',
            'POST',
            $this->pemFileMock,
            $this->payloadMock
        );
    }
}

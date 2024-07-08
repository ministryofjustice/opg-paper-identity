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

class RequestSignerTest extends TestCase
{
    private AwsSecret|MockObject $pemFileMock;
    private SecretsManagerClient $secretsManagerClient;
    private Result $privateResult;
    private const PATH = '/api/endpoint';
    private const METHOD = 'POST';
    public function setUp(): void
    {
        $this->pemFileMock = $this->createMock(AwsSecret::class);

        $this->secretsManagerClient = new SecretsManagerClient([
            'endpoint' => getenv('SECRETS_MANAGER_ENDPOINT'),
            'region' => 'eu-west-1'
        ]);
        $this->privateResult = $this->secretsManagerClient->getSecretValue([
            'SecretId' => 'local/paper-identity/yoti/private-key',
        ]);
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
            ->willReturn($this->privateResult['SecretString']);

        $signedMessage = RequestSigner::generateSignature(
            self::PATH,
            self::METHOD,
            $this->pemFileMock,
            $payload
        );
        $messageToSign = self::METHOD . '&' . self::PATH . '&' . base64_encode($payload);
        /** @var array $publicKeyResult */
        $publicKeyResult = $this->secretsManagerClient->getSecretValue([
            'SecretId' => 'local/paper-identity/yoti/public-key',
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
        $this->pemFileMock->method('getValue')->willReturn('');

        $this->expectException(PemFileException::class);

        RequestSigner::generateSignature(
            '/api/endpoint',
            'POST',
            $this->pemFileMock,
            'payloadBase64String'
        );
    }
}

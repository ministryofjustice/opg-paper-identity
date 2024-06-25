<?php

declare(strict_types=1);

namespace ApplicationTest\Yoti\Http;

use Application\Aws\Secrets\AwsSecret;
use Application\Aws\Secrets\AwsSecretsCache;
use Application\Yoti\Http\Exception\PemFileException;
use Application\Yoti\Http\RequestSigner;
use Aws\SecretsManager\SecretsManagerClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Application\Yoti\Http\Exception\RequestSignException;
use Application\Yoti\Http\Payload;

class RequestSignerTest extends TestCase
{
    private Payload|MockObject $payloadMock;
    private AwsSecret|MockObject $pemFileMock;

    private const PRIVATE_KEY = __DIR__ . '/../../../TestData/test-private-key.pem';

    private const PEM_FILE = __DIR__ . '/../../../../../../../scripts/localstack/private_key.pem';

    private const PUBLIC_KEY = __DIR__ . '/../../../TestData/test-public-key.pem';
    private const PATH = '/api/endpoint';
    private const METHOD = 'POST';
    public function setUp(): void
    {
        $this->payloadMock = $this->createMock(Payload::class);
        $this->pemFileMock = $this->createMock(AwsSecret::class);
    }

    /**
     * @covers ::generateSignature
     * @psalm-suppress PossiblyUndefinedMethod
     * @psalm-suppress PossiblyInvalidArgument
     */
    public function testGenerateSignature(): void
    {
        $this->payloadMock->method('toBase64')->willReturn('payloadBase64String');

        $secretsManagerClient = new SecretsManagerClient([
            'endpoint' => getenv('SECRETS_MANAGER_ENDPOINT'),
            'region' => 'eu-west-1'
        ]);
        $privateResult = $secretsManagerClient->getSecretValue([
            'SecretId' => 'local/paper-identity/private-key',
        ]);

        $this->pemFileMock->expects($this->atLeastOnce())
            ->method("getValue")
            ->willReturn($privateResult['SecretString']);

        $signedMessage = RequestSigner::generateSignature(
            self::PATH,
            self::METHOD,
            $this->pemFileMock,
            $this->payloadMock,
        );
        $messageToSign = self::METHOD . '&' . self::PATH . '&' . $this->payloadMock->toBase64();
        /** @var array $publicKeyResult */
        $publicKeyResult = $secretsManagerClient->getSecretValue([
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
            ->willReturn(file_get_contents(self::PRIVATE_KEY));

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

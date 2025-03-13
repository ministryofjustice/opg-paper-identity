<?php

declare(strict_types=1);

namespace ApplicationTest\Feature\Controller;

use DateInterval;
use DateTimeImmutable;
use Laminas\Http\Headers;
use Laminas\Http\Request;
use Laminas\Test\PHPUnit\Controller\AbstractControllerTestCase;
use Lcobucci\JWT\Builder as JWTBuilder;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Builder;
use PHPUnit\Framework\Attributes\DataProvider;

class ListenerTest extends AbstractControllerTestCase
{
    private Builder $builder;

    public function setUp(): void
    {
        $this->builder = new Builder(new JoseEncoder(), ChainedFormatter::default());

        $this->setApplicationConfig(include __DIR__ . '/../../../../../../config/application.config.php');

        parent::setUp();
    }

    public function testFailsWithoutHeader(): void
    {
        $this->dispatch('/some-url');

        $this->assertResponseStatusCode(401);
    }

    public function testAllowlist(): void
    {
        $this->dispatch('/health-check');

        $this->assertResponseStatusCode(200);
    }

    private function addSignedTokenHeader(JWTBuilder $tokenBuilder): void
    {
        /** @var non-empty-string $secret */
        $secret = getenv('API_JWT_KEY');

        $signer = new Sha256();
        $signingKey = InMemory::plainText($secret);

        $token = $tokenBuilder
            ->getToken($signer, $signingKey)
            ->toString();

        /** @var Request $request */
        $request = $this->getRequest();

        /** @var Headers $headers */
        $headers = $request->getHeaders();

        $headers->addHeaderLine('Authorization', "Bearer {$token}");
    }

    public function testValidJwt(): void
    {
        $now = new DateTimeImmutable();

        $token = $this->builder
            ->canOnlyBeUsedAfter($now->sub(new DateInterval('PT1S')))
            ->issuedAt($now->sub(new DateInterval('PT10S')))
            ->expiresAt($now->add(new DateInterval('PT10S')))
            ->relatedTo('user14@opg.example');

        $this->addSignedTokenHeader($token);

        $this->dispatch('/no-exist', 'GET');

        $this->assertResponseStatusCode(404);
    }

    /**
     * @return array<string, array{?DateTimeImmutable, ?DateTimeImmutable}>
     */
    public static function invalidJwtProvider(): array
    {
        $now = new DateTimeImmutable();

        return [
            'missing iat' => [null, $now->add(new DateInterval('PT5M'))],
            'missing exp' => [$now->sub(new DateInterval('PT5M')), null],
            'expired' => [$now->sub(new DateInterval('PT5M')), $now->sub(new DateInterval('PT5M'))],
            'not yet valid' => [$now->add(new DateInterval('PT5M')), $now->add(new DateInterval('PT5M'))],
        ];
    }

    /**
     * @dataProvider invalidJwtProvider
     */
    public function testInvalidJwts(?DateTimeImmutable $issuedAt, ?DateTimeImmutable $expiresAt): void
    {
        $token = $this->builder->relatedTo('system.admin@opg.example');

        if ($issuedAt) {
            $token = $token->issuedAt($issuedAt);
        }

        if ($expiresAt) {
            $token = $token->expiresAt($expiresAt);
        }

        $this->addSignedTokenHeader($token);

        $this->dispatch('/no-exist', 'GET');

        $this->assertResponseStatusCode(401);
    }
}

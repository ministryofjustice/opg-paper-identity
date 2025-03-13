<?php

declare(strict_types=1);

namespace ApplicationTest\Unit\Auth;

use Application\Auth\JwtGenerator;
use DateTimeImmutable;
use Lcobucci\Clock\FrozenClock;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use PHPUnit\Framework\TestCase;

class JwtGeneratorTest extends TestCase
{
    public function testIssueToken()
    {
        $clock = new FrozenClock(new DateTimeImmutable('2022-06-18T10:28:45Z'));

        $generator = new JwtGenerator($clock, 'alongstringthatcouldbeusedasapassphrase');
        $generator->setSub('my-identity');

        $tokenString = $generator->issueToken();

        $parser = new Parser(new JoseEncoder());
        $token = $parser->parse($tokenString);

        $this->assertInstanceOf(UnencryptedToken::class, $token);
        assert($token instanceof UnencryptedToken);

        $this->assertEquals('my-identity', $token->claims()->get('sub'));
        $this->assertEquals('2022-06-18T10:28:44+00:00', $token->claims()->get('nbf')->format('c'));
        $this->assertEquals('2022-06-18T10:28:45+00:00', $token->claims()->get('iat')->format('c'));
        $this->assertEquals('2022-06-18T10:29:15+00:00', $token->claims()->get('exp')->format('c'));
    }
}

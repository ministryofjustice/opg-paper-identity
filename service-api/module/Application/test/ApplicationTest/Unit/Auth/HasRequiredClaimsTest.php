<?php

declare(strict_types=1);

namespace ApplicationTest\ApplicationTest\Unit\Auth;

use Application\Auth\HasRequiredClaims;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\DataSet;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Token\Signature;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HasRequiredClaimsTest extends TestCase
{
    public function testRequiresUnencryptedToken(): void
    {
        $token = $this->createMock(Token::class);

        $this->expectExceptionMessage('You should pass a plain token');

        (new HasRequiredClaims())->assert($token);
    }

    public static function provideInvalidClaimSets(): array
    {
        return [
            [['iat' => 105, 'sub' => 'user'], 'The token does not have the claim "exp"'],
            [['exp' => 100, 'sub' => 'user'], 'The token does not have the claim "iat"'],
            [['exp' => 100, 'iat' => 105], 'The token does not have the claim "sub"'],
            [['exp' => 100, 'iat' => 105, 'sub' => 'user'], null],
        ];
    }

    #[DataProvider('provideInvalidClaimSets')]
    public function testRequiresClaims(array $claims, ?string $expectedError): void
    {
        $token = new Plain(
            new DataSet([], ''),
            new DataSet($claims, ''),
            new Signature('', ''),
        );

        if ($expectedError) {
            $this->expectExceptionMessage($expectedError);
        } else {
            $this->expectNotToPerformAssertions();
        }

        (new HasRequiredClaims())->assert($token);
    }
}

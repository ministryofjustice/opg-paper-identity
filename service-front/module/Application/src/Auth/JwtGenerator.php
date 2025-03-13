<?php

namespace Application\Auth;

use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Builder;
use Psr\Clock\ClockInterface;

class JwtGenerator
{
    private string $sub = '';

    public function __construct(private readonly ClockInterface $clock, private readonly string $passphrase)
    {
    }

    public function setSub(string $sub)
    {
        $this->sub = $sub;
    }

    public function issueToken(): string
    {
        $tokenBuilder = Builder::new(new JoseEncoder(), ChainedFormatter::default());
        $algorithm = new Sha256();
        $signingKey = InMemory::plainText($this->passphrase);

        $now = $this->clock->now();
        $token = $tokenBuilder
            ->relatedTo($this->sub)
            ->canOnlyBeUsedAfter($now->modify('-1 second'))
            ->issuedAt($now)
            ->expiresAt($now->modify('+30 seconds'))
            ->getToken($algorithm, $signingKey);

        return $token->toString();
    }
}

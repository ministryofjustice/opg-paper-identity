<?php

namespace Application\Auth;

use DateMalformedStringException;
use Lcobucci\JWT\Encoding\CannotEncodeContent;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\CannotSignPayload;
use Lcobucci\JWT\Signer\Ecdsa\ConversionFailed;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\InvalidKeyProvided;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Builder;
use Psr\Clock\ClockInterface;
use RuntimeException;

class JwtGenerator
{
    private string $sub = '';

    /**
     * @param non-empty-string $passphrase
     */
    public function __construct(private readonly ClockInterface $clock, private readonly string $passphrase)
    {
    }

    public function setSub(string $sub): void
    {
        $this->sub = $sub;
    }

    /**
     * @return non-empty-string
     */
    public function issueToken(): string
    {
        $tokenBuilder = Builder::new(new JoseEncoder(), ChainedFormatter::default());
        $algorithm = new Sha256();
        $signingKey = InMemory::plainText($this->passphrase);

        if ($this->sub === '') {
            throw new RuntimeException('Could not determine user sub');
        }

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

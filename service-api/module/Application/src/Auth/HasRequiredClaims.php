<?php

declare(strict_types=1);

namespace Application\Auth;

use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\RegisteredClaims;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint;
use Lcobucci\JWT\Validation\ConstraintViolation;

final class HasRequiredClaims implements Constraint
{
    public function assert(Token $token): void
    {
        if (! $token instanceof UnencryptedToken) {
            throw ConstraintViolation::error('You should pass a plain token', $this);
        }

        $claims = $token->claims();
        $requiredClaims = [RegisteredClaims::ISSUED_AT, RegisteredClaims::EXPIRATION_TIME, RegisteredClaims::SUBJECT];

        foreach ($requiredClaims as $requiredClaim) {
            if (! $claims->has($requiredClaim)) {
                throw ConstraintViolation::error('The token does not have the claim "' . $requiredClaim . '"', $this);
            }
        }
    }
}

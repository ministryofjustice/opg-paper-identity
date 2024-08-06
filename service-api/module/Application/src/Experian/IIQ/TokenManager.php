<?php

declare(strict_types=1);

namespace Application\Experian\IIQ;

class TokenManager
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     * Constructor is called by laminas-di
     */
    public function __construct(private readonly WaspClient $authClient)
    {
    }

    public function getToken(): string
    {
        $x = $this->authClient->LoginWithCertificate([
            'application' => 'opg-paper-identity',
            'checkIP' => true,
        ]);

        $token = $x->LoginWithCertificateResult;

        return base64_encode($token);
    }
}

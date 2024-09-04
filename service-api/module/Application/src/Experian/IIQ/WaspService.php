<?php

declare(strict_types=1);

namespace Application\Experian\IIQ;

use Application\Experian\IIQ\Soap\WaspClient;

class WaspService
{
    public function __construct(private readonly WaspClient $client)
    {
    }

    public function loginWithCertificate(): string
    {
        $response = $this->client->LoginWithCertificate(['service' => 'opg-paper-identity', 'checkIP' => true]);
        $token = $response->LoginWithCertificateResult;

        return base64_encode($token);
    }
}

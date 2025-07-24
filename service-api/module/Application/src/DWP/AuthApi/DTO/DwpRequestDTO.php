<?php

declare(strict_types=1);

namespace Application\DWP\AuthApi\DTO;

use Application\Services\Auth\DTO\RequestDTO;

class DwpRequestDTO extends RequestDTO
{
    public readonly string $grantType;
    public readonly string $clientId;
    public readonly string $clientSecret;

    public function __construct(array $requestArray) {
        $this->grantType = $requestArray['grant-type'];
        $this->clientId = $requestArray['client-id'];
        $this->clientSecret = $requestArray['client-secret'];
    }

    public function toArray(): array
    {
        return [
            'grant_type' => $this->grantType,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ];
    }
}

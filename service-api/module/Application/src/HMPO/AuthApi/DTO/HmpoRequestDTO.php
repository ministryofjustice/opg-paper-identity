<?php

declare(strict_types=1);

namespace Application\HMPO\AuthApi\DTO;

use Application\Services\Auth\DTO\RequestDTO;

//TODO: do we want this to be jsonserializable...
class HmpoRequestDTO extends RequestDTO
{
    public readonly string $grantType;
    public readonly string $clientId;
    public readonly string $secret;

    public function __construct(array $requestArray) {
        $this->grantType = $requestArray['grant-type'];
        $this->clientId = $requestArray['client-id'];
        $this->secret = $requestArray['client-secret'];
    }

    public function toArray(): array
    {
        return [
            'grantType' => $this->grantType,
            'clientId' => $this->clientId,
            'secret' => $this->secret,
        ];
    }
}

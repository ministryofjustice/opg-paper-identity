<?php

declare(strict_types=1);

namespace Application\HMPO\AuthApi\DTO;

use Application\Services\Auth\DTO\RequestDTO;

class HmpoRequestDTO extends RequestDTO
{
    public function toArray(): array
    {
        return [
            'grantType' => $this->grantType,
            'clientId' => $this->clientId,
            'secret' => $this->clientSecret,
        ];
    }
}

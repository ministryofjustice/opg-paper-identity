<?php

declare(strict_types=1);

namespace Application\DWP\AuthApi\DTO;

use Application\Services\Auth\DTO\RequestDTO;

class DwpRequestDTO extends RequestDTO
{
    public function toArray(): array
    {
        return [
            'grant_type' => $this->grantType,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ];
    }
}

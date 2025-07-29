<?php

declare(strict_types=1);

namespace Application\HMPO\AuthApi;

use Application\Services\Auth\AuthApiService;
use Application\Services\Auth\AuthApiException;
use Ramsey\Uuid\Uuid;

class HmpoAuthApiService extends AuthApiService
{
    public function makeHeaders(): array
    {
        assert(
            ! is_null($this->headerOptions) && array_key_exists('X-API-Key', $this->headerOptions),
            'X-API-Key must be in headerOptions'
        );

        return [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'X-API-Key' => $this->headerOptions['X-API-Key'],
            'X-REQUEST-ID' => strval(Uuid::uuid1()),
            'X-DVAD-NETWORK-TYPE' => 'api',
            'User-Agent' => 'hmpo-opg-client',
        ];
    }
}

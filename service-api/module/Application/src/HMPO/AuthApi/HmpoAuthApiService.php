<?php

declare(strict_types=1);

namespace Application\HMPO\AuthApi;

use Application\HMPO\AuthApi\DTO\HmpoResponseDTO;
use Application\Services\Auth\AuthApiService;
use Ramsey\Uuid\Uuid;

class HmpoAuthApiService extends AuthApiService
{
    public const AUTH_ENDPOINT = '/auth/token';
    public const CACHE_NAME = 'hmpo_access_token';
    public string $responseDtoClass = HmpoResponseDTO::class;

    public function makeHeaders(): array
    {
        return [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'X-API-Key' => $this->headerOptions['X-API-Key'],
            'X-REQUEST-ID' => strval(Uuid::uuid1()),
            'X-DVAD-NETWORK-TYPE' => 'api',
            'User-Agent' => 'hmpo-opg-client',
        ];
    }
}

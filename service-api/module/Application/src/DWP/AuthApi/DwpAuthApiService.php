<?php

declare(strict_types=1);

namespace Application\DWP\AuthApi;

use Application\DWP\AuthApi\DTO\DwpResponseDTO;
use Application\Services\Auth\AuthApiService;

class DwpAuthApiService extends AuthApiService
{
    public const AUTH_ENDPOINT = '/citizen-information/oauth2/token';
    public const CACHE_NAME = 'dwp_access_token';
    public string $responseDtoClass = DwpResponseDTO::class;

    public function makeHeaders(): array
    {
        return [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
    }
}

<?php

declare(strict_types=1);

namespace Application\DWP\AuthApi;

use Application\Services\Auth\AuthApiService;

class DwpAuthApiService extends AuthApiService
{
    public function makeHeaders(): array
    {
        return [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
    }
}

<?php

declare(strict_types=1);

namespace ApplicationTest\Feature\Services\DWP\AuthApi\DTO;

use Application\DWP\AuthApi\DTO\ResponseDTO;
use PHPUnit\Framework\TestCase;

class DwpAuthResponseDTOTest extends TestCase
{
    private ResponseDTO $dwpAuthResponseDTO;

    private array $data;

    public function setUp(): void
    {
        parent::setUp();

        $this->data = [
            'access_token' => 'access_token',
            'expires_in' => 123456789,
            'token_type' => 'Bearer',
        ];


        $this->dwpAuthResponseDTO = new ResponseDTO(
            $this->data['access_token'],
            $this->data['expires_in'],
            $this->data['token_type'],
        );
    }

    public function testAccessToken(): void
    {
        $this->assertEquals('access_token', $this->dwpAuthResponseDTO->accessToken());
    }

    public function testArray(): void
    {
        $this->assertEquals($this->data, $this->dwpAuthResponseDTO->toArray());
    }
}

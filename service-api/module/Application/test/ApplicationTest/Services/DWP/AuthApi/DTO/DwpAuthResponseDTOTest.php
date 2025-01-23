<?php

declare(strict_types=1);

namespace ApplicationTest\ApplicationTest\Services\Experian\AuthApi\DTO;

use Application\Experian\Crosscore\AuthApi\DTO\ResponseDTO;
use PHPUnit\Framework\TestCase;

class DwpAuthResponseDTOTest extends TestCase
{
    private ResponseDTO $experianCrosscoreAuthResponseDTO;

    private array $data;

    public function setUp(): void
    {
        parent::setUp();

        $this->data = [
            'access_token' => 'accessToken',
            'refresh_token' => 'refreshToken',
            'issued_at' => 'issuedAt',
            'expires_in' => 'expiresIn',
            'token_type' => 'tokenType'
        ];

        $this->experianCrosscoreAuthResponseDTO = new ResponseDTO(
            $this->data['access_token'],
            $this->data['refresh_token'],
            $this->data['issued_at'],
            $this->data['expires_in'],
            $this->data['token_type'],
        );
    }

    public function testAccessToken(): void
    {
        $this->assertEquals('accessToken', $this->experianCrosscoreAuthResponseDTO->accessToken());
    }

    public function testArray(): void
    {
        $this->assertEquals($this->data, $this->experianCrosscoreAuthResponseDTO->toArray());
    }
}

<?php

declare(strict_types=1);

namespace ApplicationTest\Feature\Services\Experian\AuthApi\DTO;

use Application\Experian\Crosscore\AuthApi\DTO\ResponseDTO;
use PHPUnit\Framework\TestCase;

class ExperianCrosscoreAuthResponseDTOTest extends TestCase
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

    public function testRefreshToken(): void
    {
        $this->assertEquals('refreshToken', $this->experianCrosscoreAuthResponseDTO->refreshToken());
    }

    public function testIssuedAt(): void
    {
        $this->assertEquals('issuedAt', $this->experianCrosscoreAuthResponseDTO->issuedAt());
    }

    public function testExpiresIn(): void
    {
        $this->assertEquals('expiresIn', $this->experianCrosscoreAuthResponseDTO->expiresIn());
    }

    public function testTokenType(): void
    {
        $this->assertEquals('tokenType', $this->experianCrosscoreAuthResponseDTO->tokenType());
    }
    public function testArray(): void
    {
        $this->assertEquals($this->data, $this->experianCrosscoreAuthResponseDTO->toArray());
    }
}

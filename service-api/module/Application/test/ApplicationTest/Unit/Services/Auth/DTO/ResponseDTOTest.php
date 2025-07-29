<?php

declare(strict_types=1);

namespace ApplicationTest\Feature\Services\DWP\AuthApi\DTO;

use Application\Services\Auth\DTO\ResponseDTO;
use PHPUnit\Framework\TestCase;

class ResponseDTOTest extends TestCase
{
    private ResponseDTO $responseDTO;

    private array $data;

    public function setUp(): void
    {
        parent::setUp();

        $this->data = [
            'access_token' => 'access_token',
            'expires_in' => 1800,
            'token_type' => 'Bearer',
        ];

        $this->responseDTO = new ResponseDTO(
            $this->data['access_token'],
            $this->data['expires_in'],
            $this->data['token_type'],
        );
    }

    public function testAccessToken(): void
    {
        $this->assertEquals('access_token', $this->responseDTO->accessToken());
    }

    public function testExpiresIn(): void
    {
        $this->assertEquals(1800, $this->responseDTO->expiresIn());
    }
}

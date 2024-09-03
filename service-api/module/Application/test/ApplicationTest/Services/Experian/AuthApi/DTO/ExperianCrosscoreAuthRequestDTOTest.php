<?php

declare(strict_types=1);

namespace ApplicationTest\Services\Experian\AuthApi\DTO;

use Application\Services\Experian\AuthApi\DTO\ExperianCrosscoreAuthRequestDTO;
use PHPUnit\Framework\TestCase;

class ExperianCrosscoreAuthRequestDTOTest extends TestCase
{
    private readonly ExperianCrosscoreAuthRequestDTO $experianCrosscoreAuthRequestDTO;

    private array $data;

    public function setUp(): void
    {
        parent::setUp();

        $this->data = [
            'userName' => 'userName',
            'password' => 'password',
            'clientId' => 'clientId',
            'clientSecret' => 'clientSecret',
        ];

        $this->experianCrosscoreAuthRequestDTO = new ExperianCrosscoreAuthRequestDTO(
            $this->data['userName'],
            $this->data['password'],
            $this->data['clientId'],
            $this->data['clientSecret']
        );
    }

    public function testUserName(): void
    {
        $this->assertEquals('userName', $this->experianCrosscoreAuthRequestDTO->userName());
    }

    public function testPassword(): void
    {
        $this->assertEquals('password', $this->experianCrosscoreAuthRequestDTO->password());
    }

    public function testClientId(): void
    {
        $this->assertEquals('clientId', $this->experianCrosscoreAuthRequestDTO->clientId());
    }

    public function testClientSecret(): void
    {
        $this->assertEquals('clientSecret', $this->experianCrosscoreAuthRequestDTO->clientSecret());
    }
    public function testArray(): void
    {
        $this->assertEquals($this->data, $this->experianCrosscoreAuthRequestDTO->toArray());
    }
}

<?php

declare(strict_types=1);

namespace ApplicationTest\Feature\Services\Experian\AuthApi\DTO;

use Application\Experian\Crosscore\AuthApi\DTO\RequestDTO;
use PHPUnit\Framework\TestCase;

class ExperianCrosscoreAuthRequestDTOTest extends TestCase
{
    private RequestDTO $experianCrosscoreAuthRequestDTO;

    public function setUp(): void
    {
        parent::setUp();

        $this->experianCrosscoreAuthRequestDTO = new RequestDTO(
            'userName',
            'password',
            'clientId',
            'clientSecret'
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
        $this->assertEquals([
            'username' => 'userName',
            'password' => 'password',
            'client_id' => 'clientId',
            'client_secret' => 'clientSecret',
        ], $this->experianCrosscoreAuthRequestDTO->toArray());
    }
}

<?php

declare(strict_types=1);

namespace ApplicationTest\Services\Experian\AuthApi\DTO;

use Application\Experian\Crosscore\AuthApi\DTO\RequestDTO;
use PHPUnit\Framework\TestCase;

class DwpAuthRequestDTOTest extends TestCase
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

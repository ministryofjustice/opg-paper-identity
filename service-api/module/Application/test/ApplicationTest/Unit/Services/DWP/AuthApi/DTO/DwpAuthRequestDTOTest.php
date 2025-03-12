<?php

declare(strict_types=1);

namespace ApplicationTest\Feature\Services\DWP\AuthApi\DTO;

use Application\DWP\AuthApi\DTO\RequestDTO;
use PHPUnit\Framework\TestCase;

class DwpAuthRequestDTOTest extends TestCase
{
    private RequestDTO $dwpAuthRequestDTO;

    public function setUp(): void
    {
        parent::setUp();

        $this->dwpAuthRequestDTO = new RequestDTO(
            'client_credentials',
            'clientId',
            'clientSecret',
        );
    }
    public function testArray(): void
    {
        $this->assertEquals([
            'grant_type' => 'client_credentials',
            'client_id' => 'clientId',
            'client_secret' => 'clientSecret',
        ], $this->dwpAuthRequestDTO->toArray());
    }
}

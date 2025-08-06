<?php

declare(strict_types=1);

namespace ApplicationTest\Feature\Services\HMPO\AuthApi\DTO;

use Application\HMPO\AuthApi\DTO\RequestDTO;
use PHPUnit\Framework\TestCase;

class HmpoRequestDTOTest extends TestCase
{
    private RequestDTO $hmpoAuthRequestDTO;

    public function setUp(): void
    {
        parent::setUp();

        $this->hmpoAuthRequestDTO = new RequestDTO(
            'client_credentials',
            'clientId',
            'clientSecret',
        );
    }
    public function testArray(): void
    {
        $this->assertEquals([
            'grantType' => 'client_credentials',
            'clientId' => 'clientId',
            'secret' => 'clientSecret',
        ], $this->hmpoAuthRequestDTO->toArray());
    }
}

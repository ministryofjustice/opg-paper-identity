<?php

declare(strict_types=1);

namespace ApplicationTest\Feature\Services\HMPO\AuthApi\DTO;

use Application\HMPO\AuthApi\DTO\HmpoRequestDTO;
use PHPUnit\Framework\TestCase;

class HmpoRequestDTOTest extends TestCase
{
    private HmpoRequestDTO $hmpoAuthRequestDTO;

    public function setUp(): void
    {
        parent::setUp();

        $this->hmpoAuthRequestDTO = new HmpoRequestDTO(
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

<?php

declare(strict_types=1);

namespace ApplicationTest\Feature\Services\DWP\DwpApi\DTO;

use Application\DWP\DwpApi\DTO\DetailsRequestDTO;
use PHPUnit\Framework\TestCase;

class DetailsRequestDTOTest extends TestCase
{
    private DetailsRequestDTO $detailsRequestDTO;

    private string $id = 'test-id';
    public function setUp(): void
    {
        parent::setUp();

        $this->detailsRequestDTO = new DetailsRequestDTO($this->id);
    }

    public function testId(): void
    {
        $this->assertEquals('test-id', $this->detailsRequestDTO->id());
    }
}

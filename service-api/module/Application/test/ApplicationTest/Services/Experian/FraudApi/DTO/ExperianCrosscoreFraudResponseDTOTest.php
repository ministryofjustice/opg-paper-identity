<?php

declare(strict_types=1);

namespace ApplicationTest\ApplicationTest\Services\Experian\FraudApi\DTO;

use Application\Services\Experian\FraudApi\DTO\ExperianCrosscoreFraudResponseDTO;
use PHPUnit\Framework\TestCase;

class ExperianCrosscoreFraudResponseDTOTest extends TestCase
{
    private readonly ExperianCrosscoreFraudResponseDTO $experianCrosscoreFraudResponseDTO;

    private array $data;

    public function setUp(): void
    {
        parent::setUp();

        $this->data = [];

        $this->experianCrosscoreFraudResponseDTO = new ExperianCrosscoreFraudResponseDTO(
            $this->data
        );
    }

    public function testArray(): void
    {
        $this->assertEquals(
            $this->data,
            $this->experianCrosscoreFraudResponseDTO->toArray()
        );
    }
}

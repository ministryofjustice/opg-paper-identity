<?php

declare(strict_types=1);

namespace ApplicationTest\Yoti;

use Application\Model\Entity\CaseData;
use Application\Yoti\SessionConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SessionConfigTest extends TestCase
{
    private CaseData|MockObject $caseMock;
    public function setUp(): void
    {
        parent::setUp();

        $this->caseMock = $this->createMock(CaseData::class);

        // Create an instance of SUT with mocked dependencies
        $this->sut = new SessionConfig();
    }


}

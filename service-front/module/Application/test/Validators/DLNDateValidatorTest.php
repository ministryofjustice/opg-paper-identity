<?php

declare(strict_types=1);

namespace ApplicationTest\Validators;

use Application\Validators\DLNDateValidator;
use PHPUnit\Framework\TestCase;

class DLNDateValidatorTest extends TestCase
{
    protected DLNDateValidator $dlnDateValidator;

    public function setUp(): void
    {
        parent::setUp();

        $this->dlnDateValidator = new DLNDateValidator();
    }

    /**
     * @dataProvider dlnData
     */
    public function testValidator(mixed $dln, bool $valid): void
    {
        $this->assertEquals($valid, $this->dlnDateValidator->isValid($dln));
    }

    public static function dlnData(): array
    {
        return [
            ['', false],
            [null, false],
            ['no', false],
            ['yes', true],
        ];
    }
}

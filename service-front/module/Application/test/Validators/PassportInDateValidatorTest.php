<?php

declare(strict_types=1);

namespace ApplicationTest\Validators;

use Application\Validators\PassportInDateValidator;
use PHPUnit\Framework\TestCase;

class PassportInDateValidatorTest extends TestCase
{
    protected PassportInDateValidator $passportDateValidator;

    public function setUp(): void
    {
        parent::setUp();

        $this->passportDateValidator = new PassportInDateValidator();
    }

    /**
     * @dataProvider passportData
     */
    public function testValidator(mixed $passport, bool $valid): void
    {
        $this->assertEquals($this->passportDateValidator->isValid($passport), $valid);
    }

    public static function passportData(): array
    {
        return [
            ['', false],
            [null, false],
            ['--', false],
            ['no', false],
            ['yes', true],
        ];
    }
}

<?php

declare(strict_types=1);

namespace ApplicationTest\Validators;

use Application\Validators\PassportDateValidator;
use PHPUnit\Framework\TestCase;

class PassportDateValidatorTest extends TestCase
{
    protected PassportDateValidator $passportDateValidator;

    public function setUp(): void
    {
        parent::setUp();

        $this->passportDateValidator = new PassportDateValidator();
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
            ['no', false],
            ['yes', true],
        ];
    }
}

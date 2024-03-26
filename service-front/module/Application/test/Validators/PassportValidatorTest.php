<?php

declare(strict_types=1);

namespace ApplicationTest\Validators;

use Application\Validators\PassportValidator;
use PHPUnit\Framework\TestCase;

class PassportValidatorTest extends TestCase
{
    protected PassportValidator $passportValidator;

    public function setUp(): void
    {
        parent::setUp();

        $this->passportValidator = new PassportValidator();
    }

    /**
     * @dataProvider passportData
     */
    public function testValidator(string $passport, bool $valid): void
    {
        $this->assertEquals($valid, $this->passportValidator->isValid($passport));
    }

    public static function passportData(): array
    {
        return [
            ['123456789', true],
            ['12345678Q', false],
            ['12345678', false],
        ];
    }
}

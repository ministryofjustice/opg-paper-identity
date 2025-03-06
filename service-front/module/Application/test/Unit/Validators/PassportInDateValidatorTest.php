<?php

declare(strict_types=1);

namespace ApplicationTest\Unit\Validators;

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
    public function testValidator(mixed $passport, ?string $error, bool $valid): void
    {
        $this->assertEquals($valid, $this->passportDateValidator->isValid($passport));
        if (! is_null($error)) {
            $this->assertNotEmpty($this->passportDateValidator->getMessages()[$error]);
        }
    }

    public static function passportData(): array
    {
        return [
            ['', 'passport_confirm', false],
            [null, 'passport_confirm', false],
            ['--', null, false],
            ['no', 'passport_date', false],
            ['yes', null, true],
        ];
    }
}

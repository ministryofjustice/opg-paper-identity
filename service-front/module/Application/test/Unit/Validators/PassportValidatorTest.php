<?php

declare(strict_types=1);

namespace ApplicationTest\Unit\Validators;

use Application\Validators\PassportValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PassportValidatorTest extends TestCase
{
    protected PassportValidator $passportValidator;

    public function setUp(): void
    {
        parent::setUp();

        $this->passportValidator = new PassportValidator();
    }

    #[DataProvider('passportData')]
    public function testValidator(string $passport, ?string $error, bool $valid): void
    {
        $this->assertEquals($valid, $this->passportValidator->isValid($passport));
        if (! is_null($error)) {
            $this->assertNotEmpty($this->passportValidator->getMessages()[$error]);
        }
    }

    public static function passportData(): array
    {
        return [
            ['123456789', null, true],
            ['12345678Q', 'passport_format', false],
            ['12345678', 'passport_count', false],
            ['', 'passport_empty', false],
        ];
    }
}

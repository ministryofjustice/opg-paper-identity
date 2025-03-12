<?php

declare(strict_types=1);

namespace ApplicationTest\Unit\Validators;

use Application\Validators\BirthDateValidator;
use PHPUnit\Framework\TestCase;

class BirthDateValidatorTest extends TestCase
{
    protected BirthDateValidator $birthDateValidator;

    public function setUp(): void
    {
        parent::setUp();

        $this->birthDateValidator = new BirthDateValidator();
    }

    /**
     * @dataProvider birthDateData
     */
    public function testValidator(mixed $birthDate, bool $valid, string $expectedErrorKey = null): void
    {
        $this->assertEquals($valid, $this->birthDateValidator->isValid($birthDate));

        if (! $valid) {
            $this->assertArrayHasKey($expectedErrorKey, $this->birthDateValidator->getMessages());
        }
    }

    public static function birthDateData(): array
    {
        return [
            // Valid dates
            ['1990-01-01', true],
            ['1996-02-29', true], // Leap year
            ['1990-1-1', true], // Single-digit month and day

            // Empty or invalid placeholders
            ['', false, BirthDateValidator::DATE_EMPTY],
            [null, false, BirthDateValidator::DATE_EMPTY],
            ['--', false, BirthDateValidator::DATE_EMPTY],

            // Invalid date formats
            ['invalid-date', false, BirthDateValidator::DATE_FORMAT],
            ['1997-02-29', false, BirthDateValidator::DATE_FORMAT], // Impossible date (non-leap year)

            // Future dates
            [(new \DateTime('+1 day'))->format('Y-m-d'), false, BirthDateValidator::DATE_FUTURE],

            // Under 18 years old
            [(new \DateTime('-17 years'))->format('Y-m-d'), false, BirthDateValidator::DATE_18],
        ];
    }
}

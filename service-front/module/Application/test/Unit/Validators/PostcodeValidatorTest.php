<?php

declare(strict_types=1);

namespace ApplicationTest\Unit\Validators;

use Application\Validators\PostcodeValidator;
use PHPUnit\Framework\TestCase;

class PostcodeValidatorTest extends TestCase
{
    protected PostcodeValidator $postcodeValidator;

    public function setUp(): void
    {
        parent::setUp();

        $this->postcodeValidator = new PostcodeValidator();
    }

    /**
     * @dataProvider ninoData
     */
    public function testValidator(string $postcode, ?array $context, bool $expected): void
    {
        $this->assertEquals($expected, $this->postcodeValidator->isValid($postcode, $context));
    }

    public static function ninoData(): array
    {
        $ukContext = ['country' => 'United Kingdom'];
        $nonUkContext = ['country' => 'Australia'];
        return [
            ['SW1A 1AA', null, true],
            ['JC8 5XZ', null, true],
            ['SW18 1JX', null, true],
            ['HA62NU', null, true],
            ['HSD 3NU', null, false],
            ['A123 2NU', null, false],
            ['HA6 NNU', null, false],
            ['WC2H 7LTa', null, false],
            ['WC2H', null, false],
            ['SW1A 1AA', $ukContext, true],
            ['HSD 3NU', $ukContext, false],
            ['WC2H', $ukContext, false],
            ['SW1A 1AA', $nonUkContext, true],
            ['HSD 3NU', $nonUkContext, true],
            ['', $nonUkContext, true],
            ['123456', $nonUkContext, true]
        ];
    }
}

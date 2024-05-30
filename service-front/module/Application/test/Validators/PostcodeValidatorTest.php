<?php

declare(strict_types=1);

namespace ApplicationTest\Validators;

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
    public function testValidator(string $postcode, bool $valid): void
    {
        $this->assertEquals($valid, $this->postcodeValidator->isValid($postcode));
    }

    public static function ninoData(): array
    {
        return [
            ['SW1A 1AA', true],
            ['JC8 5XZ', true],
            ['SW18 1JX', true],
            ['HA62NU', true],
            ['HSD 3NU', false],
            ['A123 2NU', false],
            ['HA6 NNU', false],
            ['WC2H 7LTa', false],
            ['WC2H', false],
        ];
    }
}

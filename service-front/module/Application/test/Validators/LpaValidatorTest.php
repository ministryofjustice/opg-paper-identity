<?php

declare(strict_types=1);

namespace ApplicationTest\Validators;

use Application\Validators\LpaUidValidator;
use PHPUnit\Framework\TestCase;

class LpaValidatorTest extends TestCase
{
    protected LpaUidValidator $lpaValidator;

    public function setUp(): void
    {
        parent::setUp();

        $this->lpaValidator = new LpaUidValidator();
    }

    /**
     * @dataProvider lpaData
     */
    public function testValidator(string $lpa, bool $valid): void
    {
        $this->assertEquals($this->lpaValidator->isValid($lpa), $valid);
    }

    public static function lpaData(): array
    {
        return [
            ['M-0000-0000-0000', true],
            ['M-0000-0000-000t', true],
            ['M-0000-0000-1234', true],
            ['M-0000-0000-123', false],
            ['T-0000-0000-1234', false],
            ['M-0-00-0000-1234', false],
            ['-0-00-0000-1234', false],
        ];
    }
}

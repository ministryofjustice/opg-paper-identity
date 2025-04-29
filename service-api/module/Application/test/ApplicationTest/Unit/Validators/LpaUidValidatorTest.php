<?php

declare(strict_types=1);

namespace ApplicationTest\Unit\Validators;

use Application\Validators\LpaUidValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LpaUidValidatorTest extends TestCase
{
    protected LpaUidValidator $lpaValidator;

    public function setUp(): void
    {
        parent::setUp();

        $this->lpaValidator = new LpaUidValidator();
    }

    #[DataProvider('lpaData')]
    public function testValidator(string $lpa, bool $valid): void
    {
        $this->assertEquals($valid, $this->lpaValidator->isValid($lpa));
    }

    public static function lpaData(): array
    {
        return [
            ['M-0000-0000-0000', true],
            ['M-0000-0000-1234', true],
            ['M-0000-0000-000t', false],
            ['M-0000-0000-123', false],
            ['T-0000-0000-1234', false],
            ['M-0-00-0000-1234', false],
            ['-0-00-0000-1234', false],
            ['M-0000-0000-1234-1234', false],
            ['PREFIX-M-0000-0000-1234', false],
        ];
    }
}

<?php

declare(strict_types=1);

namespace ApplicationTest\Validators;

use Application\Validators\DLNValidator;
use PHPUnit\Framework\TestCase;

class DLNValidatorTest extends TestCase
{
    protected DLNValidator $dlnValidator;

    public function setUp(): void
    {
        parent::setUp();

        $this->dlnValidator = new DLNValidator();
    }

    /**
     * @dataProvider dlnData
     */
    public function testValidator(string $dln, bool $valid): void
    {
        $this->assertEquals($valid, $this->dlnValidator->isValid($dln));
    }

    public static function dlnData(): array
    {
        return [
            ['SMITH 712037 SS9FX', true],
            ['JONES 206038 MJ9FX', true],
            ['CHOLM 903031 DT9FX', true],
            ['SM1TH 712037 SS9FX', false],
            ['SMITH A12037 SS9FX', false],
            ['SMITH 7120378 SS9FX', false],
            ['SMITH 712037 059FX', false],
            ['SMITHY 712037 SS9FX', false],
        ];
    }
}

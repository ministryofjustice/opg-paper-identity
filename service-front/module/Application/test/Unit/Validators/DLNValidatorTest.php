<?php

declare(strict_types=1);

namespace ApplicationTest\Unit\Validators;

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
    public function testValidator(string $dln, ?string $error, bool $valid): void
    {
        $this->assertEquals($valid, $this->dlnValidator->isValid($dln));
        if (! is_null($error)) {
            $this->assertNotEmpty($this->dlnValidator->getMessages()[$error]);
        }
    }

    public static function dlnData(): array
    {
        return [
            ['SMITH 712037 SS9FX', null, true],
            ['JONES 206038 MJ9FX', null, true],
            ['CHOLM 903031 DT9FX', null, true],
            ['SM1TH 712037 SS9FX', 'DLN_format', false],
            ['SMITH A12037 SS9FX', 'DLN_format', false],
            ['SMITH 7120378 SS9FX', 'DLN_count', false],
            ['SMITH 712037 059FX', 'DLN_format', false],
            ['SMITHY 712037 SS9FX', 'DLN_count', false],
            ['', 'DLN_empty', false],
        ];
    }
}

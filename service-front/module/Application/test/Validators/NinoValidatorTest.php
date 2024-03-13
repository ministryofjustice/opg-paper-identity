<?php

declare(strict_types=1);

namespace ApplicationTest\Validators;

use Application\Validators\NinoValidator;
use PHPUnit\Framework\TestCase;

class NinoValidatorTest extends TestCase
{
    protected NinoValidator $ninoValidator;

    public function setUp(): void
    {
        parent::setUp();

        $this->ninoValidator = new NinoValidator();
    }

    /**
     * @dataProvider ninoData
     */
    public function testValidator(string $nino, bool $valid): void
    {
        $this->assertEquals($this->ninoValidator->isValid($nino), $valid);
    }

    public static function ninoData()
    {
        return [
            ['AA 11 22 33 A', true],
            ['BB 44 55 66 B', true],
            ['ZZ 67 89 00 C', true],
            ['AA 11 22 33 E', false],
            ['DA 11 22 33 A', false],
            ['FA 11 22 33 A', false],
            ['AO 11 22 33 A', false],
        ];
    }
}

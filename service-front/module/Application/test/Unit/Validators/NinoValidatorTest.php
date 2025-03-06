<?php

declare(strict_types=1);

namespace ApplicationTest\Unit\Validators;

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
    public function testValidator(string $nino, ?string $error, bool $valid): void
    {
        $this->assertEquals($valid, $this->ninoValidator->isValid($nino));
        if (! is_null($error)) {
            $this->assertNotEmpty($this->ninoValidator->getMessages()[$error]);
        }
    }

    public static function ninoData(): array
    {
        return [
            ['AA 11 22 33 A', null, true],
            ['BB 44 55 66 B', null, true],
            ['ZZ 67 89 00 C', null, true],
            ['AA 11 22 33 E', null, false],
            ['DA 11 22 33 A', 'nino_format', false],
            ['FA 11 22 33 A', 'nino_format', false],
            ['AO 11 22 33 A', 'nino_format', false],
            ['AO 11 22 33 Q', 'nino_format', false],
            ['AO 11 22 33 F', 'nino_format', false],
            ['AO 11 22 33 L', 'nino_format', false],
            ['', 'nino_empty', false],
            ['not valid',  'nino_count', false],
        ];
    }
}

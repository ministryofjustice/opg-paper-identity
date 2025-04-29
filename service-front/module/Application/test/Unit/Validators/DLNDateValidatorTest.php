<?php

declare(strict_types=1);

namespace ApplicationTest\Unit\Validators;

use Application\Validators\DLNDateValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DLNDateValidatorTest extends TestCase
{
    protected DLNDateValidator $dlnDateValidator;

    public function setUp(): void
    {
        parent::setUp();

        $this->dlnDateValidator = new DLNDateValidator();
    }

    #[DataProvider('dlnData')]
    public function testValidator(mixed $dln, ?string $error, bool $valid): void
    {
        $this->assertEquals($valid, $this->dlnDateValidator->isValid($dln));
        if (! is_null($error)) {
            $this->assertNotEmpty($this->dlnDateValidator->getMessages()[$error]);
        }
    }

    public static function dlnData(): array
    {
        return [
            ['', 'DLN_confirm', false],
            [null, 'DLN_confirm', false],
            ['no', 'DLN_date', false],
            ['yes', null, true],
        ];
    }
}

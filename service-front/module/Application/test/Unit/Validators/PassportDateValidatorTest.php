<?php

declare(strict_types=1);

namespace ApplicationTest\Unit\Validators;

use Application\Validators\PassportDateValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PassportDateValidatorTest extends TestCase
{
    protected PassportDateValidator $passportDateValidator;

    public function setUp(): void
    {
        parent::setUp();

        $this->passportDateValidator = new PassportDateValidator();
    }

    #[DataProvider('passportData')]
    public function testValidator(mixed $passportDate, bool $valid): void
    {
        $this->assertEquals($valid, $this->passportDateValidator->isValid($passportDate));
    }

    public static function passportData(): array
    {
        $currentDate = new \DateTime();
        $periodNPass = "P16M";
        $periodNFail = "P18M";

        return [
            ['2007-12-12', false],
            ['1999-03-31', false],
            [$currentDate->format('Y-m-d'), true],
            [$currentDate->sub(new \DateInterval($periodNPass))->format('Y-m-d'), true],
            [$currentDate->sub(new \DateInterval($periodNFail))->format('Y-m-d'), false],
        ];
    }
}

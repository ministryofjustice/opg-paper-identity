<?php

declare(strict_types=1);

namespace ApplicationTest\Unit\Validators;

use Application\Validators\Enum;
use PHPUnit\Framework\TestCase;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses
enum Suit: string
{
    case Hearts = 'H';
    case Diamonds = 'D';
    case Clubs = 'C';
    case Spades = 'S';
}

// phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses
enum Face: string
{
    case Jack = 'J';
    case Queen = 'Q';
    case King = 'K';
}

// phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses
class EnumTest extends TestCase
{
    /**
     * @dataProvider provideIsValid
     */
    public function testIsValid(mixed $value, bool $expected, ?string $errorMessage): void
    {
        $sut = new Enum(['enum' => Suit::class]);
        self::assertEquals($expected, $sut->isValid($value));

        if ($errorMessage !== null) {
            self::assertEquals($errorMessage, current($sut->getMessages()));
        }
    }

    /**
     * @return array{mixed, bool, ?string}[]
     */
    public static function provideIsValid(): array
    {
        return [
            [null, false, 'The value was not valid for ApplicationTest\Unit\Validators\Suit'],
            ['', false, 'The value was not valid for ApplicationTest\Unit\Validators\Suit'],
            ['Hearts', false, 'The value was not valid for ApplicationTest\Unit\Validators\Suit'],
            ['HS', false, 'The value was not valid for ApplicationTest\Unit\Validators\Suit'],
            ['X', false, 'The value was not valid for ApplicationTest\Unit\Validators\Suit'],
            ['H', true, null],
            ['S', true, null],
            [Suit::Hearts, true, null],
            [Face::Jack, false, 'The value was not valid for ApplicationTest\Unit\Validators\Suit'],
        ];
    }
}

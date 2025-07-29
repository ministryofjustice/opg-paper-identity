<?php

declare(strict_types=1);

namespace ApplicationTest\Feature\Services\HMPO\HmpoApi\DTO;

use Application\HMPO\HmpoApi\DTO\ValidatePassportResponseDTO;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ValidatePassportResponseDTOTest extends TestCase
{
    #[DataProvider('constructData')]
    public function testToArray(
        bool $validationResult,
        ?bool $passportCancelled,
        ?bool $passportLostStolen,
        array $expected
    ): void {
        $result = [
            'validationResult' => $validationResult
        ];
        if (! is_null($passportCancelled)) {
            $result['passportCancelled'] = $passportCancelled;
        }
        if (! is_null($passportCancelled)) {
            $result['passportLostStolen'] = $passportLostStolen;
        }
        $response = [
            'data' => [
                'validatePassport' => $result
            ]
        ];

        $dto = new ValidatePassportResponseDTO($response);

        $this->assertEquals($dto->toArray(), $expected);
    }

    public static function constructData(): array
    {
        return [
            'invalid, null cancelled/lost' => [
                false,
                null,
                null,
                ['validationResult' => false, 'passportCancelled' => false, 'passportLostStolen' => false]
            ],
            'valid, null cancelled/lost' => [
                true,
                null,
                null,
                ['validationResult' => true, 'passportCancelled' => false, 'passportLostStolen' => false]
            ],
            'valid, populated cancelled/lost' => [
                true,
                true,
                true,
                ['validationResult' => true, 'passportCancelled' => true, 'passportLostStolen' => true]
            ],
        ];
    }

    #[DataProvider('isValidData')]
    public function testIsValid(bool $validationResult, ?bool $passportCancelled, bool $expected): void
    {
        $response = [
            'data' => [
                'validatePassport' => [
                    'validationResult' => $validationResult,
                    'passportCancelled' => $passportCancelled,
                ]
            ]
        ];

        $dto = new ValidatePassportResponseDTO($response);

        $this->assertEquals($dto->isValid(), $expected);
    }

    public static function isValidData(): array
    {
        return [
            'valid' => [true, false, true],
            'cancelled' => [true, true, false],
            'invalid' => [false, false, false],
        ];
    }
}

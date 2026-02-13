<?php

declare(strict_types=1);

namespace Application\Forms;

use Application\Validators\BirthDateValidator;
use Laminas\Form\Annotation;
use Laminas\Hydrator\ObjectPropertyHydrator;
use Laminas\Validator\NotEmpty;

/**
 * @psalm-suppress MissingConstructor
 */
#[Annotation\Hydrator(ObjectPropertyHydrator::class)]
class VoucherBirthDate
{
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    #[Annotation\Validator(BirthDateValidator::class, options: [
        "messages" => [
            BirthDateValidator::DATE_18  => "The person vouching must be 18 years or older."
        ]
    ])]
    // empty values are handled in the BirthDateValidator
    #[Annotation\Validator(NotEmpty::class, options: [
        'type' => NotEmpty::ALL & ~NotEmpty::STRING,
        'messages' => [
            NotEmpty::IS_EMPTY => 'Enter a date of birth',
        ],
    ])]
    public mixed $date;
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    public string $dob_day;
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    public string $dob_month;
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    public string $dob_year;
}

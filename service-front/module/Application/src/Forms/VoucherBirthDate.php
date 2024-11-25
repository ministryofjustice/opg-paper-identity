<?php

declare(strict_types=1);

namespace Application\Forms;

use Application\Validators\BirthDateValidator;
use Laminas\Form\Annotation;
use Laminas\Hydrator\ObjectPropertyHydrator;

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

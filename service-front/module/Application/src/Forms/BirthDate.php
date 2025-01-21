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
class BirthDate
{
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    #[Annotation\Validator(BirthDateValidator::class, options: [
        "messages" => [
            BirthDateValidator::DATE_EMPTY => "Enter their date of birth",
            BirthDateValidator::DATE_FORMAT => "Date of birth must be a valid date",
            BirthDateValidator::DATE_18  => "The person must be 18 years or older."
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

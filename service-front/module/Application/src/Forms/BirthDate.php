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
    #[Annotation\Validator(BirthDateValidator::class)]
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

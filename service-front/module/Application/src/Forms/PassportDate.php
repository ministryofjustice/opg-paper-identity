<?php

declare(strict_types=1);

namespace Application\Forms;

use Application\Validators\PassportDateValidator;
use Laminas\Form\Annotation;
use Laminas\Hydrator\ObjectPropertyHydrator;

/**
 * @psalm-suppress MissingConstructor
 */
#[Annotation\Hydrator(ObjectPropertyHydrator::class)]
class PassportDate
{
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    #[Annotation\Validator(PassportDateValidator::class)]
    public mixed $passport_date;

    public string $passport_issued_day;
    public string $passport_issued_month;
    public string $passport_issued_year;
}

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
    #[Annotation\Validator(PassportDateValidator::class, options: ['expiry_allowance' => '+5 year'])]
    public mixed $passport_date;
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    public string $passport_issued_day;
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    public string $passport_issued_month;
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    public string $passport_issued_year;
}

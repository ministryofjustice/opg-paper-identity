<?php

declare(strict_types=1);

namespace Application\Forms;

use Application\Validators\PassportValidator;
use Application\Validators\PassportInDateValidator;
use Laminas\Form\Annotation;
use Laminas\Hydrator\ObjectPropertyHydrator;

/**
 * @psalm-suppress MissingConstructor
 */
#[Annotation\Hydrator(ObjectPropertyHydrator::class)]
class PassportNumber
{
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    #[Annotation\Validator(PassportValidator::class)]
    public string $passport;

    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    #[Annotation\Validator(PassportInDateValidator::class)]
    public mixed $inDate;

    public string $passport_issued_day;
    public string $passport_issued_month;
    public string $passport_issued_year;
}

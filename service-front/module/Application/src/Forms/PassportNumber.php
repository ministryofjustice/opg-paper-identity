<?php

declare(strict_types=1);

namespace Application\Forms;

use Application\Validators\PassportValidator;
use Application\Validators\PassportInDateValidator;
use Laminas\Form\Annotation;
use Laminas\Hydrator\ObjectPropertyHydrator;
use Laminas\Validator\NotEmpty;

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

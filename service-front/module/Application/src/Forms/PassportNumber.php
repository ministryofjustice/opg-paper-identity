<?php

declare(strict_types=1);

namespace Application\Forms;

use Application\Validators\PassportValidator;
use Application\Validators\PassportDateValidator;
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
    #[Annotation\Validator(PassportDateValidator::class)]
    public mixed $inDate;
}

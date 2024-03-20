<?php

declare(strict_types=1);

namespace Application\Forms;

use Application\Validators\DLNValidator;
use Application\Validators\DLNDateValidator;
use Laminas\Form\Annotation;
use Laminas\Hydrator\ObjectPropertyHydrator;

/**
 * @psalm-suppress MissingConstructor
 */
#[Annotation\Hydrator(ObjectPropertyHydrator::class)]
class DrivingLicenceNumber
{
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    #[Annotation\Validator(DLNValidator::class)]
    public string $dln;

    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    #[Annotation\Validator(DLNDateValidator::class)]
    public mixed $inDate;
}

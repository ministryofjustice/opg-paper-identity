<?php

declare(strict_types=1);

namespace Application\Forms;

use Application\Validators\NinoValidator;
use Laminas\Form\Annotation;
use Laminas\Hydrator\ObjectPropertyHydrator;

/**
 * @psalm-suppress MissingConstructor
 */
#[Annotation\Hydrator(ObjectPropertyHydrator::class)]
class NationalInsuranceNumber
{
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    #[Annotation\Validator(NinoValidator::class)]
    public string $nino;
}

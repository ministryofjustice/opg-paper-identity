<?php

declare(strict_types=1);

namespace Application\Forms;

use Application\Validators\NinoValidator;
use Laminas\Form\Annotation;
use Laminas\Hydrator\ObjectPropertyHydrator;

/**
 * @psalm-suppress MissingConstructor
 * @implements FormTemplate<array{nino: string}>
 */
#[Annotation\Hydrator(ObjectPropertyHydrator::class)]
class NationalInsuranceNumber implements FormTemplate
{
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    #[Annotation\Validator(NinoValidator::class)]
    public string $nino;
}

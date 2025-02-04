<?php

declare(strict_types=1);

namespace Application\Forms;

use Application\Validators\NinoValidator;
use Laminas\Form\Annotation;
use Laminas\Hydrator\ObjectPropertyHydrator;
use Laminas\Validator\NotEmpty;

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
    // empty values are handled in the NinoValidator
    #[Annotation\Validator(NotEmpty::class, options: [
        'type' => NotEmpty::ALL & ~NotEmpty::STRING
    ])]
    #[Annotation\Validator(NinoValidator::class)]
    public string $nino;
}

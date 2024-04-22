<?php

declare(strict_types=1);

namespace Application\Forms;

use Application\Validators\LpaValidator;
use Laminas\Form\Annotation;
use Laminas\Hydrator\ObjectPropertyHydrator;

/**
 * @psalm-suppress MissingConstructor
 */
#[Annotation\Hydrator(ObjectPropertyHydrator::class)]
class LpaReferenceNumber
{
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    #[Annotation\Validator(LpaValidator::class)]
    public string $lpa;
}

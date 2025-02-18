<?php

declare(strict_types=1);

namespace Application\Forms;

use Application\Validators\LpaUidValidator;
use Laminas\Form\Annotation;
use Laminas\Hydrator\ObjectPropertyHydrator;

/**
 * @psalm-suppress MissingConstructor
 * @implements FormTemplate<array{lpa: string}>
 */
#[Annotation\Hydrator(ObjectPropertyHydrator::class)]
class LpaReferenceNumberAdd implements FormTemplate
{
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    #[Annotation\Validator(LpaUidValidator::class)]
    public string $add_lpa_number;
}

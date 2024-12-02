<?php

declare(strict_types=1);

namespace Application\Forms;

use Application\Validators\LpaUidValidator;
use Laminas\Form\Annotation;
use Laminas\Hydrator\ObjectPropertyHydrator;
use Laminas\Validator\NotEmpty;

/**
 * @psalm-suppress MissingConstructor
 * @implements FormTemplate<array{lpa: string}>
 */
#[Annotation\Hydrator(ObjectPropertyHydrator::class)]
class LpaReferenceNumber implements FormTemplate
{
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    #[Annotation\Validator(LpaUidValidator::class)]
    // empty values are handled in the LpaUidValidator
    #[Annotation\Validator(NotEmpty::class, options: [
        'type' => NotEmpty::ALL & ~NotEmpty::STRING
    ])]
    public string $lpa;
}

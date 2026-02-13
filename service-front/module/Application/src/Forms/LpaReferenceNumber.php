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
class LpaReferenceNumber implements FormTemplate
{
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    #[Annotation\Validator(LpaUidValidator::class, options: [
        'message' => 'Not a valid LPA number. Enter an LPA number to continue!'
    ])]
    public string $lpa;
}

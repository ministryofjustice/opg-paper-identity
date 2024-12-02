<?php

declare(strict_types=1);

namespace Application\Forms;

use Application\Validators\PostcodeValidator;
use Laminas\Form\Annotation;
use Laminas\Hydrator\ObjectPropertyHydrator;
use Laminas\Validator\NotEmpty;

/**
 * @psalm-suppress MissingConstructor
 * @implements FormTemplate<array{postcode: string}>
 */
#[Annotation\Hydrator(ObjectPropertyHydrator::class)]
class Postcode implements FormTemplate
{
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    #[Annotation\Validator(PostcodeValidator::class)]
    // empty values are handled in the PostcodeValidator
    #[Annotation\Validator(NotEmpty::class, options: [
        'type' => NotEmpty::ALL & ~NotEmpty::STRING
    ])]    public mixed $postcode;
}

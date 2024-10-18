<?php

declare(strict_types=1);

namespace Application\Forms;

use Application\Validators\PostcodeValidator;
use Laminas\Form\Annotation;
use Laminas\Hydrator\ObjectPropertyHydrator;

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
    public mixed $postcode;
}

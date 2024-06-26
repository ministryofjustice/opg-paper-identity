<?php

declare(strict_types=1);

namespace Application\Forms;

use Application\Validators\PostcodeValidator;
use Laminas\Form\Annotation;
use Laminas\Hydrator\ObjectPropertyHydrator;

/**
 * @psalm-suppress MissingConstructor
 */
#[Annotation\Hydrator(ObjectPropertyHydrator::class)]
class PostOfficePostcode
{
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    #[Annotation\Validator(PostcodeValidator::class)]
    public mixed $selected_postcode;
}

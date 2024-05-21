<?php

declare(strict_types=1);

namespace Application\Forms;

use Laminas\Form\Annotation;
use Laminas\Hydrator\ObjectPropertyHydrator;

/**
 * @psalm-suppress MissingConstructor
 */
#[Annotation\Hydrator(ObjectPropertyHydrator::class)]
class PostOfficeNumericCode
{
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    public mixed $postoffice;
}

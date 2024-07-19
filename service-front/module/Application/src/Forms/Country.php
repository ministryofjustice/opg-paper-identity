<?php

declare(strict_types=1);

namespace Application\Forms;

use Laminas\Form\Annotation;
use Laminas\Hydrator\ObjectPropertyHydrator;
use Laminas\Validator\NotEmpty;

/**
 * @psalm-suppress MissingConstructor
 */
#[Annotation\Hydrator(ObjectPropertyHydrator::class)]
class Country
{
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    #[Annotation\Validator(NotEmpty::class, ['message' => 'Please select a country'])]
    public mixed $country;

    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    #[Annotation\Validator(NotEmpty::class, ['message' => 'Please select a document'])]
    public mixed $id_method;
}

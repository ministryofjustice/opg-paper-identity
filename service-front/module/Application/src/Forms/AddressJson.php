<?php

declare(strict_types=1);

namespace Application\Forms;

use Laminas\Form\Annotation;
use Laminas\Hydrator\ObjectPropertyHydrator;
use Laminas\Validator\NotEmpty;

/**
 * @psalm-suppress MissingConstructor
 * @implements FormTemplate<array{address_json: string}>
 */
#[Annotation\Hydrator(ObjectPropertyHydrator::class)]
class AddressJson implements FormTemplate
{
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    #[Annotation\Validator(NotEmpty::class, options: [
        "messages" => [
            NotEmpty::IS_EMPTY  => "Please choose an address"
        ]
    ])]
    public mixed $address_json;
}

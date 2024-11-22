<?php

declare(strict_types=1);

namespace Application\Forms;

use Laminas\Form\Annotation;
use Laminas\Hydrator\ObjectPropertyHydrator;
use Laminas\Validator\NotEmpty;

/**
 * @psalm-suppress MissingConstructor
 * @implements FormTemplate<array{
 *   assistance: string,
 *   details: string,
 * }>
 */
#[Annotation\Hydrator(ObjectPropertyHydrator::class)]
class FinishIDCheck implements FormTemplate
{
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    #[Annotation\Validator(NotEmpty::class, options: [
        "messages" => [
            NotEmpty::IS_EMPTY  => "Please select Yes or No"
        ]
    ])]
    public mixed $assistance;

    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    public string $details;
}

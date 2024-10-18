<?php

declare(strict_types=1);

namespace Application\Forms;

use Laminas\Form\Annotation;
use Laminas\Hydrator\ObjectPropertyHydrator;
use Laminas\Validator\NotEmpty;

/**
 * @psalm-suppress MissingConstructor
 * @implements FormTemplate<array{
 *   reason: string,
 *   notes: string,
 * }>
 */
#[Annotation\Hydrator(ObjectPropertyHydrator::class)]
class AbandonFlow implements FormTemplate
{
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    #[Annotation\Validator(NotEmpty::class, options: [
        "messages" => [
            NotEmpty::IS_EMPTY  => "Please choose a reason"
        ]
    ])]
    public mixed $reason;

    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    public string $notes;
}

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
class ConfirmVouching implements FormTemplate
{
     /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    #[Annotation\Validator(NotEmpty::class, options: [
        "messages" => [
            NotEmpty::IS_EMPTY  => "Confirm eligibility to continue"
        ]
    ])]
    public mixed $eligibility = null;

    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    #[Annotation\Validator(NotEmpty::class, options: [
        "messages" => [
            NotEmpty::IS_EMPTY  => "Confirm declaration to continue"
        ]
    ])]
    public mixed $declaration = null;

    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    public mixed $continue = null;

    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    public mixed $tryDifferent = null;
}

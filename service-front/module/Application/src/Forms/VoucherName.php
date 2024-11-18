<?php

declare(strict_types=1);

namespace Application\Forms;

use Laminas\Form\Annotation;
use Laminas\Hydrator\ObjectPropertyHydrator;
use Laminas\Validator\NotEmpty;

/**
 * @psalm-suppress MissingConstructor
 * @implements FormTemplate<array{
 *   firstName: string,
 *   lastName: string,
 * }>
 */
#[Annotation\Hydrator(ObjectPropertyHydrator::class)]
class VoucherName implements FormTemplate
{
     /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    #[Annotation\Validator(NotEmpty::class)]
    public mixed $firstName = null;

    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    #[Annotation\Validator(NotEmpty::class)]
    public mixed $lastName = null;
}

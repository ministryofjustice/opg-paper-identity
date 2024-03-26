<?php

declare(strict_types=1);

namespace Application\Forms;


use Laminas\Form\Annotation;
use Laminas\Hydrator\ObjectPropertyHydrator;

/**
 * @psalm-suppress MissingConstructor
 */
#[Annotation\Hydrator(ObjectPropertyHydrator::class)]
class IdQuestions
{
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    public int $one;
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    public int $two;
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    public int $three;
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    public int $four;
}

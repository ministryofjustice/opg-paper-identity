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
    public string $one;
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    public string $two;
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    public string $three;
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    public string $four;
}

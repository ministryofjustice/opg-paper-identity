<?php

declare(strict_types=1);

namespace ApplicationTest\Unit\Helpers\DTO;

use Laminas\Form\Annotation;
use Laminas\Hydrator\ObjectPropertyHydrator;

/**
 * @psalm-suppress MissingConstructor
 */
#[Annotation\Hydrator(ObjectPropertyHydrator::class)]
class TestValidator
{
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    public string $test;
}

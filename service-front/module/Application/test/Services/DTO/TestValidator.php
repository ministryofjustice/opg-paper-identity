<?php

declare(strict_types=1);

namespace ApplicationTest\Services\DTO;

use Laminas\Form\Annotation;
use Laminas\Hydrator\ObjectPropertyHydrator;

/**
 * @psalm-suppress MissingConstructor
 */
#[Annotation\Hydrator(ObjectPropertyHydrator::class)]
class TestValidator
{
    public string $test;
}

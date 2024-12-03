<?php

declare(strict_types=1);

namespace Application\Forms;

use Application\Validators\PostcodeValidator;
use Application\Validators\AddressFieldValidator;
use Application\Validators\CountryValidator;
use Laminas\Form\Annotation;
use Laminas\Hydrator\ObjectPropertyHydrator;

/**
 * @psalm-suppress MissingConstructor
 * @implements FormTemplate<array{
 *   line1: string,
 *   line2: string,
 *   line3: string,
 *   town: string,
 *   postcode: string,
 *   country: string,
 * }>
 */
#[Annotation\Hydrator(ObjectPropertyHydrator::class)]
class Address implements FormTemplate
{
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    #[Annotation\Validator(AddressFieldValidator::class)]
    public mixed $line1;
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    public mixed $line2;
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    public mixed $line3;

    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    #[Annotation\Validator(AddressFieldValidator::class)]
    public mixed $town;

    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    #[Annotation\Validator(PostcodeValidator::class)]
    public mixed $postcode;

    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    public mixed $country;
}

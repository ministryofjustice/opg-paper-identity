<?php

declare(strict_types=1);

namespace Application\Forms;

use Application\Validators\PostcodeValidator;
use Application\Validators\AddressFieldValidator;
use Application\Validators\CountryValidator;
use Laminas\Form\Annotation;
use Laminas\Hydrator\ObjectPropertyHydrator;
use Laminas\Validator\NotEmpty;

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
class AddressInput implements FormTemplate
{
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    #[Annotation\Validator(NotEmpty::class, options: [
        'type' => NotEmpty::ALL,
        "messages" => [
            NotEmpty::IS_EMPTY  => "Enter an address"
        ]
    ])]
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
    #[Annotation\Validator(NotEmpty::class, options: [
        'type' => NotEmpty::ALL,
        "messages" => [
            NotEmpty::IS_EMPTY  => "Enter a town or city"
        ]
    ])]
    public mixed $town;

    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    #[Annotation\Validator(PostcodeValidator::class)]
    // empty values are handled in the PostcodeValidator
    #[Annotation\Validator(NotEmpty::class, options: [
        'type' => NotEmpty::ALL & ~NotEmpty::STRING
    ])]
    public mixed $postcode;

    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    public mixed $country;
}

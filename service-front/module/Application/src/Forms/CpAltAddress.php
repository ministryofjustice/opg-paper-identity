<?php

declare(strict_types=1);

namespace Application\Forms;

use Application\Validators\PostcodeValidator;
use Application\Validators\AddressFieldValidator;
use Laminas\Form\Annotation;
use Laminas\Hydrator\ObjectPropertyHydrator;

/**
 * @psalm-suppress MissingConstructor
 */
#[Annotation\Hydrator(ObjectPropertyHydrator::class)]
class CpAltAddress
{
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    #[Annotation\Validator(AddressFieldValidator::class)]
    public mixed $addressLine1;
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    public mixed $addressLine2;
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    public mixed $addressLine3;

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

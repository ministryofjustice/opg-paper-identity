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
    public mixed $address_line_1;

    public mixed $address_line_2;
    
    public mixed $address_line_3;

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
}

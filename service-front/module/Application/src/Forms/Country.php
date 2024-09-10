<?php

declare(strict_types=1);

namespace Application\Forms;

use Application\Validators\CountryDocumentValidator;
use Application\Validators\CountryValidator;
use Laminas\Form\Annotation;
use Laminas\Hydrator\ObjectPropertyHydrator;
use Laminas\Validator\NotEmpty;

/**
 * @psalm-suppress MissingConstructor
 */
#[Annotation\Hydrator(ObjectPropertyHydrator::class)]
class Country
{
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    #[Annotation\Validator(CountryValidator::class)]
    #[Annotation\Validator(NotEmpty::class, options: [NotEmpty::NULL])]
    public mixed $country;
}

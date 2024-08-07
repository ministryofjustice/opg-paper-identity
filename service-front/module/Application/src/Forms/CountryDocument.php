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
class CountryDocument
{
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    #[Annotation\Validator(CountryDocumentValidator::class)]
    #[Annotation\Validator(NotEmpty::class, options: [
        "messages" => [
            NotEmpty::IS_EMPTY  => "Please choose a type of document"
        ]
    ])]
    public mixed $id_method;
}

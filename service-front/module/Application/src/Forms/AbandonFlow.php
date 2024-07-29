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
class AbandonFlow
{
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    public string $route;

    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    #[Annotation\Validator(NotEmpty::class, options: [
        "messages" => [
            NotEmpty::IS_EMPTY  => "Please choose a reason"
        ]
    ])]
    public mixed $reason;

    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    public string $notes;
}

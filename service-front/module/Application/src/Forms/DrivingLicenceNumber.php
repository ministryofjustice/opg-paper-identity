<?php

declare(strict_types=1);

namespace Application\Forms;

use Application\Validators\DLNValidator;
use Application\Validators\DLNDateValidator;
use Laminas\Form\Annotation;
use Laminas\Hydrator\ObjectPropertyHydrator;
use Laminas\Validator\NotEmpty;

/**
 * @psalm-suppress MissingConstructor
 * @implements FormTemplate<array{dln: string, inDate: ?string}>
 */
#[Annotation\Hydrator(ObjectPropertyHydrator::class)]
class DrivingLicenceNumber implements FormTemplate
{
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    // empty values are handled in the DLNValidator
    #[Annotation\Validator(NotEmpty::class, options: [
        'type' => NotEmpty::ALL & ~NotEmpty::STRING
    ])]
    #[Annotation\Validator(DLNValidator::class)]
    public string $dln;

    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    //cannot figure out why NotEmpty trumps DLNDateValidator
    #[Annotation\Validator(NotEmpty::class, options: [
        "messages" => [
            NotEmpty::IS_EMPTY  => 'Please choose yes or no'
        ]
    ])]
    #[Annotation\Validator(DLNDateValidator::class)]
    public mixed $inDate = null;
}

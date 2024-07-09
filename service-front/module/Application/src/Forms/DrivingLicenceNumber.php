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
 */
#[Annotation\Hydrator(ObjectPropertyHydrator::class)]
class DrivingLicenceNumber
{
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    #[Annotation\Validator(DLNValidator::class)]
    public string $dln;

    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    #[Annotation\Validator(DLNDateValidator::class)]
    #[Annotation\Validator(NotEmpty::class, options: ["messages" => [NotEmpty::IS_EMPTY => "Please choose yes or no"]])]
    public mixed $inDate = null;
}

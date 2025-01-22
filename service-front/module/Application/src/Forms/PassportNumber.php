<?php

declare(strict_types=1);

namespace Application\Forms;

use Application\Validators\PassportValidator;
use Application\Validators\PassportInDateValidator;
use Laminas\Form\Annotation;
use Laminas\Hydrator\ObjectPropertyHydrator;
use Laminas\Validator\NotEmpty;

/**
 * @psalm-suppress MissingConstructor
 * @implements FormTemplate<array{passport: string}>
 */
#[Annotation\Hydrator(ObjectPropertyHydrator::class)]
class PassportNumber implements FormTemplate
{
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    // empty values are handled in the PassportValidator
    #[Annotation\Validator(NotEmpty::class, options: [
        'type' => NotEmpty::ALL & ~NotEmpty::STRING
    ])]
    #[Annotation\Validator(PassportValidator::class)]
    public string $passport;

    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    //cannot figure out why NotEmpty trumps PassportInDateValidator
    #[Annotation\Validator(NotEmpty::class, options: [
        "messages" => [
            NotEmpty::IS_EMPTY  => 'Please choose yes or no'
        ]
    ])]
    #[Annotation\Validator(PassportInDateValidator::class)]
    public mixed $inDate;

    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    public string $passport_issued_day;
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    public string $passport_issued_month;
    /**
     * @psalm-suppress PossiblyUnusedProperty
     */
    public string $passport_issued_year;
}
